# Plugin directory TODO

## v0.0.1

* Additional SQL to seed an admin account on install
* Request pull from sandbox

## v0.0.2

* Prettier plugin release page
* Merge changes between plugins
    * eg. Update sandbox plugin from live
* Back up and rollback for live plugin pushes
* Use recaptcha for captchas instead of Kohana built-in?
* HTTP basic auth in API (OAuth?)
* Loading indicator for plugin editor
* Per-plugin release list and per-release GET/PUT/POST in JSON
* Advanced search pulldown
* Offer a switch to use sandbox or not on front page
* Detect plugins on sandbox page?
* Add release via JSON API

* Localization
    * How to mark up Twig templates for l10n?
        * Use filters to run text through _()
        * Run gettext over generated PHP to find _() functions?
        * Custom script to scrape localizable strings out of templates.

