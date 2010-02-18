# Plugin directory TODO

## Bugs

See also: [bugzilla bugs][bugzilla] at Mozilla.

[bugzilla]: https://bugzilla.mozilla.org/buglist.cgi?query_format=advanced&product=Websites&component=plugins.mozilla.org&bug_status=UNCONFIRMED&bug_status=NEW&bug_status=ASSIGNED

## 1.5.0

* Blocklist parity
    * Need to support version ranges, for both application and plugins?
    * More general client-side match elements, regexes beyond just names
    * Ping blocklist for supplemental info?
    * Attempt to replace blocklist functionality? (probably not)
* Power editing mode to edit plugin definition JSON directly
* Preserve hand-tweaked JSON data.
    * Store plugin JSON in a blob in plugin (everything) and release (single release) rows.
    * One-way generation of indexed releases on import 
    * No longer regenerate JSON from releases on export
* Non-AJAX/JSON plugin editor?
    * Form-driven plugin release management
    * Show defaults in fields from plugin defaults
* Advanced search pulldown
* Minimize strings included in l10n/translations to just those needed by JS
    * Switch from .mo parsing to producing a PHP file with _() calls?

## 2.0.0

* Prettier plugin release page
* Merge changes between plugins
    * eg. Update sandbox plugin from live
* Back up and rollback for live plugin pushes
* Use recaptcha for captchas instead of Kohana built-in?
* HTTP basic auth in API (OAuth?)
* Loading indicator for plugin editor
* Per-plugin release list and per-release GET/PUT/POST in JSON
* Offer a switch to use sandbox or not on front page
    * User can currently just log out to disable sandbox involvement
    * Detect plugins on sandbox page?
* Add release via JSON API
