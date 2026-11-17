(() => {
    if (window.wakamatsuStoreMapInstalled) return;
    window.wakamatsuStoreMapInstalled = true;
    const addressCache = new Map();
    const mapInstances = new WeakMap();
    let current = null;
    let scheduled = false;

    const loadMaps = (key) => {
        if (window.google?.maps) return Promise.resolve();
        window.wakamatsuGoogleMapsPromise ||= new Promise((resolve, reject) => {
            window.wakamatsuGoogleMapsLoaded = resolve;
            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(key)}&callback=wakamatsuGoogleMapsLoaded`;
            script.async = true;
            script.onerror = reject;
            script.dataset.wakamatsuGoogleMaps = 'true';
            document.head.appendChild(script);
        });
        return window.wakamatsuGoogleMapsPromise;
    };
    const parse = (value, fallback) => {
        try { return JSON.parse(value); } catch (_) { return fallback; }
    };
    const positionOf = (row) => {
        if (!row || row.lat === null || row.lng === null || row.lat === '' || row.lng === '') return null;
        const lat = Number(row.lat), lng = Number(row.lng);
        return Number.isFinite(lat) && Number.isFinite(lng) && Math.abs(lat) <= 90 && Math.abs(lng) <= 180
            ? { lat, lng } : null;
    };
    const info = (title, detail, address) => {
        const box = document.createElement('div');
        box.style.cssText = 'font-size:13px;line-height:1.7;color:#111827;min-width:150px';
        [title, detail, address].filter(Boolean).forEach((text, index) => {
            const line = document.createElement(index === 0 ? 'strong' : 'div');
            line.textContent = text;
            box.appendChild(line);
        });
        return box;
    };
    const geocodeAddress = (address) => {
        if (!addressCache.has(address)) {
            addressCache.set(address, new Promise((resolve) => {
                new google.maps.Geocoder().geocode({ address, region: 'jp', componentRestrictions: { country: 'JP' } }, (results, status) => {
                    const point = status === 'OK' && results?.[0]?.geometry?.location;
                    const location = point ? positionOf({ lat: point.lat(), lng: point.lng() }) : null;
                    if (!location) addressCache.delete(address); // Permit recovery after temporary failures.
                    resolve(location);
                });
            }));
        }
        return addressCache.get(address);
    };
    const render = async () => {
        const wrapper = document.querySelector('[data-store-postal-map]');
        if (!wrapper) { current = null; return; }
        const element = wrapper.querySelector('#store-postal-map');
        const signature = JSON.stringify([wrapper.dataset.mapRows, wrapper.dataset.selectedStore, wrapper.dataset.apiKey]);
        if (current?.element === element && current.signature === signature) return;
        const state = { element, signature };
        current = state;
        const active = () => current === state && element.isConnected;
        const status = (message) => {
            if (active()) wrapper.querySelector('[data-map-status]').textContent = message;
        };
        status('地図を読み込んでいます…');
        try {
            await loadMaps(wrapper.dataset.apiKey);
            if (!active()) return;
            const rows = parse(wrapper.dataset.mapRows, []).filter(row => positionOf(row));
            const store = parse(wrapper.dataset.selectedStore, null);
            const bounds = new google.maps.LatLngBounds();
            let instance = mapInstances.get(element);
            if (!instance) {
                instance = { map: new google.maps.Map(element, {
                    center: rows.length ? positionOf(rows[0]) : { lat: 35.6812, lng: 139.7671 },
                    zoom: 12, mapTypeControl: false, streetViewControl: false, fullscreenControl: true,
                }), popup: new google.maps.InfoWindow(), overlays: [] };
                mapInstances.set(element, instance);
            }
            instance.overlays.forEach(overlay => overlay.setMap(null));
            instance.overlays = [];
            instance.popup.close();
            const { map, popup } = instance;
            if (rows.length) { map.setCenter(positionOf(rows[0])); map.setZoom(12); }
            let pointCount = 0;
            const fit = () => { if (pointCount > 1) map.fitBounds(bounds, 48); };
            rows.forEach(row => {
                const position = positionOf(row);
                bounds.extend(position); pointCount++;
                const circle = new google.maps.Circle({
                    map, center: position, strokeColor: '#f97316', strokeOpacity: 0.85, strokeWeight: 1,
                    fillColor: '#f97316', fillOpacity: Number(row.opacity || 0.35), radius: Number(row.radius || 600),
                });
                instance.overlays.push(circle);
                circle.addListener('click', () => {
                    popup.setPosition(position);
                    popup.setContent(info(`〒${row.postal_code}`, `${Number(row.count || 0).toLocaleString()}人 / ${row.percent}%`, row.address));
                    popup.open(map);
                });
            });
            fit();
            if (!store) { status('店舗を選択すると店舗位置を表示します。'); return; }
            const address = store.address?.trim();
            if (!address) { status('店舗住所が未登録のため、店舗ピンを表示できません。'); return; }
            status('店舗の位置を確認しています…');
            const position = await geocodeAddress(address);
            if (!active()) return; // Do not apply an old response after selecting another store.
            if (!position) { status('店舗住所の位置を取得できませんでした。店舗の住所をご確認ください。'); return; }
            const marker = new google.maps.Marker({
                map, position, title: store.name,
                icon: { path: google.maps.SymbolPath.CIRCLE, scale: 16, fillColor: '#2563eb', fillOpacity: 1, strokeColor: '#ffffff', strokeWeight: 2 },
                label: { text: '店', color: '#ffffff', fontWeight: 'bold', fontSize: '13px' },
                zIndex: 1000,
            });
            instance.overlays.push(marker);
            marker.addListener('click', () => {
                popup.setContent(info(store.name, '選択中の店舗', address));
                popup.open(map, marker);
            });
            bounds.extend(position); pointCount++;
            if (pointCount > 1) fit(); else { map.setCenter(position); map.setZoom(15); }
            status(`青い「店」：${store.name}（${address}）`);
        } catch (_) {
            if (active()) { current = null; wrapper.querySelector('[data-map-status]').textContent = '地図を読み込めませんでした。ページを再読み込みしてください。'; }
        }
    };
    const schedule = () => {
        if (scheduled) return;
        scheduled = true;
        requestAnimationFrame(() => { scheduled = false; render(); });
    };
    let hooked = false;
    const hook = () => {
        if (hooked || !window.Livewire) return;
        hooked = true;
        window.Livewire.hook('morph.updated', schedule);
    };
    document.addEventListener('DOMContentLoaded', schedule);
    document.addEventListener('livewire:navigated', schedule);
    document.addEventListener('livewire:init', hook);
    hook();
    schedule();
})();
