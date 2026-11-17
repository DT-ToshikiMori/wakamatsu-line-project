# Store dashboard review — 2026-11-18

## Implemented

- Selected store's registered address is geocoded using the existing Google Maps JavaScript API and drawn with a distinct blue 店 marker. No postal-code-center fallback is presented as a store location. Map bounds include the selected store and visible customer postal areas. Store-only maps work without visitor postal data.
- Address results are reused within the page; stale async results are discarded after switching stores. Maps are reused on filter changes, and Livewire does not morph Google Maps-owned DOM. Text is rendered with textContent in popups.
- Missing address, geocoding failure and map-load failure have explicit messages instead of silently displaying the wrong pin.
- Postal maps explicitly say top 20. Percentages include visitors without a postal code in their denominator; this is now explained.
- Rename the ambiguous repeat-rate KPI to period multi-visit rate and document its denominator. Aggregation definitions remain unchanged, including cross-store deduplication when all stores are selected.
- Preserve self-reported registration-time visit frequency, but collapse it by default and clearly distinguish it from actual visits.
- Skip store comparison aggregation when one store is selected (the table is not rendered in that mode). Count repeat visitors through a subquery instead of loading every repeat user's row into PHP.

## Retain, do not delete without a reporting decision

- The 6 KPI cards are related but not identical: check-ins, unique visitors, first-time visitors, multiple visitors, per-user visit frequency, and multi-visit share. Average and share can be secondary in a future visual redesign, but no metric was deleted.
- Gender and birth-year age groups remain useful audience breakdowns. Birth-year age is approximate rather than birthday-accurate.
- Self-reported initial visit frequency is lower priority and may be stale; now collapsed rather than removed.
- Postal ranking and map offer different exact-vs-spatial views. Neither is deleted.

## Existing limitations, not silently redefined

- These are recorded check-ins, not proof of all store transactions or all actual customers.
- All-store visitors are deduplicated across stores, while per-store rows count visitors separately; row totals can therefore differ from the all-store total.
- Summary aggregation includes historical visits to inactive stores while the store comparison table includes active stores only. Product reporting needs a policy decision before changing either population.
- Postal map/ranking only show top 20 and map points are area centers, not customer home addresses.
- General long-term retention is a different metric from 2+ check-ins within the selected period.
- Store pin accuracy depends on the registered address and Google Geocoding availability. Geocoding failures are visible; no made-up location is used.

## Verification

PHP SQLite in-memory fixtures verify selection/reset, visitor deduplication, first visits and repeat counts. Livewire rendering verifies map data and clarified labels. Node VM tests with mocked Google Maps verify store-only mode, cache reuse, stale-response rejection, failure messages, bounds, preserved customer circles and safe popup text. No external API calls or customer messages are performed by tests. Actual Google Maps browser rendering is unverified without an authenticated browser connection.
