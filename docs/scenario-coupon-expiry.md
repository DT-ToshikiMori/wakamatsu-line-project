# Initial scenario coupon expiry (2026-11-18)

Scope: production visit scenario IDs 3 and 4 only. Unclaimed coupons, including historical deliveries, expire at the recorded `visit_scenario_sends.sent_at + 60 days`. At or after this instant the claim returns the expiry notice without inserting a coupon/event. Existing issued/used/expired coupon rows and their deadlines are untouched. GOLD and other scenarios retain their prior rules.

New messages carry a random batch delivery token saved after successful API acceptance. Lookup requires authenticated user + scenario + delivery token. Historical links have no token; they can select only historical, tokenless deliveries and cannot issue coupons from newer tokenized deliveries. Among multiple tokenless deliveries the existing latest-record convention remains; old message URLs cannot be retroactively rewritten.

Already claimed deliveries return the coupon list, even after expiration, without the new unclaimed-expiry notice. The existing expiration command handles their list status.

Migration adds a nullable delivery token index and changes only target coupon bubble settings (40 to 60 days) and their text phrase. It never updates user_coupons. Schema/policy migration is intentionally forward-only because removing tokens would invalidate sent links. Before rollback, preserve token-aware lookup in any replacement code.

Reminder definitions are expiry-relative (7 and 3 days before) and follow the stored deadline. The 90-day after-last-visit churn scenarios are independent and unchanged. Existing scheduler/reminder execution behavior is otherwise unchanged.

Offline verification: `APP_KEY=<test-only-key> php vendor/bin/phpunit`. Feature tests use only SQLite :memory: and mocked LINE API. No production-send command is run during verification.
