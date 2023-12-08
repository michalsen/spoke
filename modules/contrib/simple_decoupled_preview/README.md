# Simple Decoupled Preview

A module to get node previews in JSON:API format.

This module (via included sub-module simple_decoupled_preview_jsonapi) adds a
new endpoint for all JSON:API resources of
type node, adding a `/preview` string to the end of the original JSON:API path.

For example:

* Node / Article : `/jsonapi/node/article/{UUID}/preview`
* Node / Page : `/jsonapi/node/page/{UUID}/preview`
* etc

Being a preview operation, this endpoint:

1. is not cached.
2. is authenticated, so it is only available to the same authenticated user that
   created the node preview.

Due to the limitation of #2, this module initiates a request to this api upon
clicking the node preview button and
stores the response in a custom entity uniquely identified by:

- UUID of the entity previewed.
- Language code of the entity previewed.
- User ID of the previewing user.

This combination of unique attributes allows each preview to be overwritten
every time a new preview is requested
uniquely identified by the entity UUID, language code and User ID of the user
initiating the preview.

This module provides an api endpoint to request these previews in the form of:
`/api/preview/{UUID}?uid={UID}&langcode=en`
The json response returned from this REST endpoint is consistent with the json
returned from a request to
to an endpoint provided by the Drupal jsonapi core module.

**Note:** A cron task is provided to remove expired preview entities. It is
highly encouraged to set the frequency of
this task to run at least daily to keep your database lean and mean. By design,
the preview entity is intended to be
temporary and serves no other purpose than to be returned as a response from our
own custom REST api.

Submit bug reports, feature suggestions and support requests in the
[issue queue](https://www.drupal.org/project/issues/simple_decoupled_preview).

## Requirements

This module only requires the following modules:

* Node module (core)
* JSON:API module (core)
* [REST UI](https://www.drupal.org/project/restui)

## Installation

Install as you would normally install a contributed Drupal module. For further
information, see [Installing Drupal
Modules](https://drupal.org/docs/extending-drupal/installing-drupal-modules).

## Configuration

1. Navigate to Administration > Extend and enable the module.
2. Navigate to Administration > Configuration > Web services > Simple Decoupled
   Preview to configure settings. See
   additional [documentation](https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-modules/simple-decoupled-preview#s-simple-decoupled-preview-configuration)
   for advanced setup.
3. Navigate to Administration > Configuration > Web services > REST.
4. Enable the **Simple Decoupled Preview** JSON REST resource.
    - Enable the **GET** Method
    - Enable the **json** Accepted request format.
    - Enable the desired Authentication providers.
5. Navigate to Administration > People > Permissions and enable the **Access GET
   on Simple Decoupled Preview JSON
   resource** permission for the role(s) you intend to grant access to the api.
6. Update your services.yml file if necessary to enable CORS. This is required
   to allow your decoupled front-end to make
   requests to the preview api on your Drupal site.
7. Enable display of the preview button for your node types by navigating to the
   content type administration page and
   selecting either Optional or Required in the **Preview before submitting**
   field within the Submission form settings
   tab.
8. Enable the Decoupled Preview view mode for node types by navigating to the
   Manage display tab within the content
   type administration page.
9. Click the Preview button for an unsaved node and acknowledge the iframe
   display pointing to the url on your decoupled
   front-end site as configured in step 2 appended with additional path parts to
   serve as parameters for your front-end
   preview template to consume.
   **
   Example:** `https://www.examplesite.com/preview/{bundle}/{uuid}/{langcode}/{uid}`
10. Any front-end framework can consume the api and provide the preview content
    back to the iframe. We've provided an
    example implementation for Gatsby projects documented in the npm
    package [gatsby-drupal-preview](https://www.npmjs.com/package/gatsby-drupal-preview)
    to streamline setup.

## Similar modules

- [JSON:API Node Preview](https://www.drupal.org/project/jsonapi_node_preview)
    - Simple Decoupled Preview is a heavily rewritten fork of JSON:API Node
      Preview. As is, the module provides no viable
      mechanism to return preview JSON to a decoupled frontend due to same user
      access restrictions for the user
      initiating the preview.
- [GraphQL Node Preview](https://www.drupal.org/project/graphql_node_preview)
    - Provides similar functionality to JSON:API Node Preview but is focused on
      GraphQL output rather than JSON:API and
      suffers from the same limitations.
- [Decoupled Preview](https://www.drupal.org/project/decoupled_preview)
    - Depends on decoupled router and is intended to work with Next.js front-end
      frameworks. Simple Decoupled Preview is
      designed to work with any front-end framework.

## Maintainers

- [Andy Marquis (apmsooner)](https://www.drupal.org/u/apmsooner)
- [Damien McKenna](https://www.drupal.org/u/damienmckenna)
- [Mark Shropshire (shrop)](https://www.drupal.org/u/shrop)
