http://tools.ietf.org/html/draft-tbray-http-legally-restricted-status-02

Send a 451 status code on your content that had to be removed for legal reasons.

Downsides:

* Post still has to "published" visible in archives and such -- didn't want to
go mucking about in your queries
* `status_header` fires too early to tell if a singular request
