# Plugin directory TODO

* Advanced search pulldown
* Search results
* Clone plugin to sandbox from details page
* Clone / create plugin to sandbox from installed plugins page
* Request pull from sandbox
* Request claim on plugin
* (admin) Approve claim on plugin
* (admin / claimant) Push live from sandbox
* Add sandbox ID to search criteria in API
    * Use sandbox data on installed list
        * Offer a switch to use sandbox or not
    * Detect plugins on sandbox page
* Add release to plugin in editor
    * New release based in installed plugin
    * New release based on submitted reports
    * New blank release
* Add release via JSON API
* HTTP basic auth in API (OAuth?)

* Page / bugzilla bug for soliciting ideas on detecting undetected versions

* Localization
    * How to mark up Twig templates for l10n?
        * Use filters to run text through _()
        * Run gettext over generated PHP to find _() functions?
        * Custom script to scrape localizable strings out of templates.

* Use recaptcha for captchas instead of Kohana built-in
