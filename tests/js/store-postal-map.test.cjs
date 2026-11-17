const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const code = fs.readFileSync('public/js/store-postal-map.js', 'utf8');
const tick = () => new Promise(resolve => setImmediate(resolve));
function harness(store, rows = []) {
    const maps = [], markers = [], circles = [], geocodes = [], popups = [];
    const events = {};
    const element = { isConnected: true };
    const status = { textContent: '' };
    const wrapper = {
        dataset: { selectedStore: JSON.stringify(store), mapRows: JSON.stringify(rows), apiKey: 'offline-key' },
        querySelector: selector => selector === '#store-postal-map' ? element : status,
    };
    class Overlay {
        constructor(options) { this.options = options; this.handlers = {}; }
        addListener(name, fn) { this.handlers[name] = fn; }
        setMap(map) { this.map = map; }
    }
    const google = { maps: {
        Map: class { constructor(el, options) { maps.push(this); this.options = options; } setCenter(p) { this.center = p; } setZoom(z) { this.zoom = z; } fitBounds(b) { this.bounds = b; } },
        Marker: class extends Overlay { constructor(o) { super(o); markers.push(this); } },
        Circle: class extends Overlay { constructor(o) { super(o); circles.push(this); } },
        InfoWindow: class { constructor() { popups.push(this); } close() {} setContent(c) { this.content = c; } setPosition() {} open() {} },
        LatLngBounds: class { constructor() { this.points = []; } extend(p) { this.points.push(p); } },
        Geocoder: class { geocode(request, callback) { geocodes.push({ request, callback }); } },
        SymbolPath: { CIRCLE: 0 },
    } };
    const document = {
        querySelector: () => wrapper,
        addEventListener: (name, fn) => { events[name] = fn; },
        createElement: () => ({ style: {}, children: [], appendChild(x) { this.children.push(x); } }),
    };
    const context = { window: { google }, google, document, requestAnimationFrame: fn => fn(), console };
    vm.runInNewContext(code, context);
    const resolve = (index, lat = 34.5, lng = 135.5) => geocodes[index].callback([{ geometry: { location: { lat: () => lat, lng: () => lng } } }], 'OK');
    const select = async value => { wrapper.dataset.selectedStore = JSON.stringify(value); events['livewire:navigated'](); await tick(); };
    return { maps, markers, circles, geocodes, popups, status, wrapper, resolve, select };
}
test('selected store pin works even without postal visitors; address is cached across filter rerenders', async () => {
    const h = harness({ id: 1, name: 'Alpha', address: '大阪市1' });
    await tick(); assert.equal(h.geocodes.length, 1);
    h.resolve(0); await tick();
    assert.equal(h.markers.length, 1);
    assert.equal(h.markers[0].options.label.text, '店');
    assert.equal(h.maps[0].zoom, 15);
    assert.match(h.status.textContent, /Alpha/);
    await h.select(null);
    assert.equal(h.markers[0].map, null);
    await h.select({ id: 1, name: 'Alpha', address: '大阪市1' }); await tick();
    assert.equal(h.geocodes.length, 1);
    assert.equal(h.maps.length, 1);
});
test('an older geocode response cannot draw a pin after selecting a different store', async () => {
    const h = harness({ id: 1, name: 'Alpha', address: '大阪市1' }); await tick();
    await h.select({ id: 2, name: 'Beta', address: '大阪市2' });
    h.resolve(1); await tick(); h.resolve(0); await tick();
    assert.equal(h.markers.length, 1);
    assert.equal(h.markers[0].options.title, 'Beta');
    assert.equal(h.maps.length, 1);
});
test('missing address and failed geocoding show explanations instead of an incorrect pin', async () => {
    const h = harness({ id: 1, name: 'Alpha', address: '' }, [{ lat: 34, lng: 135 }]); await tick();
    assert.equal(h.geocodes.length, 0); assert.equal(h.markers.length, 0); assert.match(h.status.textContent, /住所が未登録/);
    await h.select({ id: 1, name: 'Alpha', address: 'Unknown' });
    h.geocodes[0].callback([], 'ZERO_RESULTS'); await tick();
    assert.match(h.status.textContent, /取得できませんでした/); assert.equal(h.markers.length, 0);
});
test('map retains postal circles and bounds include store; popup uses safe text nodes', async () => {
    const h = harness({ id: 1, name: '<img src=x>', address: '大阪市1' }, [{ lat: 35, lng: 136, postal_code: '1234567', address: '<img src=x>', count: 2, percent: 50 }]);
    await tick(); h.resolve(0); await tick();
    assert.equal(h.circles.length, 1); assert.equal(h.maps[0].bounds.points.length, 2);
    h.markers[0].handlers.click(); assert.equal(h.popups[0].content.children[0].textContent, '<img src=x>');
    h.circles[0].handlers.click(); assert.equal(h.popups[0].content.children[2].textContent, '<img src=x>');
});
