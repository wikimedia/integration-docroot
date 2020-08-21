Document root for integration.wikimedia.org and doc.wikimedia.org
====

## integration.wikimedia.org

Start the server with `composer start:doc` and view <http://localhost:4001>.

The production service for this uses PHP 7.3.

See also <https://wikitech.wikimedia.org/wiki/Integration.wikimedia.org>.

The Zuul status page can be tested with sample data sets available in
`org/wikimedia/integration/zuul`:
* <http://localhost:4001/zuul/?demo=basic>.
* <http://localhost:4001/zuul/?demo=openstack>.
* <http://localhost:4001/zuul/?demo=tree>.

## doc.wikimedia.org

Start the server with `composer start:integration`, and view <http://localhost:4000>.

The production service for this uses PHP 7.0.

See also <https://wikitech.wikimedia.org/wiki/Doc.wikimedia.org>.

CI published files are available outside of the document root, one thus have to
set the environment variable `WMF_DOC_PATH` to indicate where the files are
located. We serve first from the document root and fallback to that directory
if nothing was found.

The development server uses `./dev/wmf_doc_path` which comes with several
boilerplates:
* http://localhost:4000/cover/
* http://localhost:4000/cover-extensions/
* http://localhost:4000/index/
