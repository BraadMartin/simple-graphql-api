=== Simple GraphQL API ===
Contributors: Braad
Donate link: http://braadmartin.com/
Tags: graphql, graph, api, simple, rest, json
Requires at least: 4.4
Tested up to: 4.5
Stable tag: 0.8.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

A simple read-only interface for making GraphQL-style queries with the REST API. Supports querying multiple resources across multiple resource types in a single request.

== Description ==

Normally with the WordPress REST API and REST APIs in general, you are interacting with a complete object representation of a resource. You ask for a post at `/wp-json/wp/v2/post/13` and you get back a JSON representation of that post, with all of the standard fields that you would expect. You don't have to specify which fields you get back, you just get back a complete set of fields that represent the current "state" of that resource.

GraphQL is a query language/API interface developed by Facebook that offers a different way of asking for and receiving data for a resource. With GraphQL you pass in an empty object with only the field keys you want, and the API fills up the object and passes it back to you. This allows you to define the object you want to work with from the client side, and simply let the API do the work of filling up the fields you ask for.

In the context of the 4 main WordPress resources (Posts, Terms, Comments, and Users), there are always the core fields plus any meta fields. If you want to add any extra meta fields ("custom fields") to the response you get from the core REST API endpoints you would register the extra fields you want in the response on the server side in PHP, and this is easily done and works great for many use cases. But unless you start removing fields from the default response you'll generally always get back some data that you don't end up using, and according to the official REST API documentation: "While it's tempting to modify or remove fields from responses, this **will** cause problems with API clients that expect standard responses. This includes things like mobile clients, or third party tools to help you manage your site....Changing responses is dangerous."

This plugin is an experiment that takes an approach inspired by GraphQL to optimize the data returned in the response and provide flexibility from the client side when making the request. It lets you ask for specific fields on the core resources without regard for whether they live in the primary tables (posts, terms, comments, users) or in the meta tables (postmeta, termmeta, commentmeta, usermeta), and without requiring any prior field registration on the server side. This allows you to define the object you want to work with from the client side and craft responses that are specific to your use case, and it naturally leads to smaller responses, all while taking advantage of the same object caching used by the REST API and without disturbing any of the core REST API endpoints.

This plugin also supports setting default fields for each resource using a few filters explained below and then passing the keyword `default` as a field name in the request. You'll get back the defaults you've set up in addition to any other fields you ask for, making this plugin a tool you can use to define custom objects that you can build upon from the client side and that can span multiple WordPress core resource types.

**Note**: There are security implications when using this plugin that make it potentially dangerous to install on some sites, specifically sites that store sensitive information in meta. Please read the **Meta** section below and make sure you understand the security implications of exposing your site's data over an API in this way. Use the provided filters to control how much meta you expose by default, and only use this plugin if you know you are comfortable with how much data it exposes.

**Note**: Development of this plugin is ongoing and breaking changes may come before 1.0. See the **Development Plans** section below for more information.

= Usage =

Simply pass one or multiple resource ids and the query param "fields" to endpoints for posts, terms, and comments, or send special query params to the /any/ endpoint to query multiple resources across multiple resource types in a single request, and you'll get back exactly what you ask for. You can also make traditional queries using a "query" keyword and a WP-API-style filter system described below.

The endpoints supported by this plugin include:

`
/wp-json/graph/v1/posts/{:ids}?fields=xxx,xxx&term_fields=xxx,xxx&comment_fields=xxx,xxx

/wp-json/graph/v1/terms/{:ids}?fields=xxx,xxx

/wp-json/graph/v1/comments/{:ids}?fields=xxx,xxx

/wp-json/graph/v1/any/?posts={:ids}&post_fields=xxx,xxx&terms={:ids}&term_fields=xxx,xxx&comments={:ids}&comment_fields=xxx,xxx
`

To return specific posts by ID from the **/posts/** endpoint send a request to `/wp-json/graph/v1/posts/{:ids}?fields=xxx,xxx` where `{:ids}` is a comma separated list of the post ids you want and `xxx,xxx` is a comma separated list of the fields you want, and you'll get only those fields back.

Request:

`
/wp-json/graph/v1/posts/13,17?fields=ID,post_title,post_content,some_custom_field
`

Response:

`
{
  "posts": [
    {
      "ID": 13,
      "post_title": {
        "raw": "GraphQL FTW",
        "rendered": "GraphQL FTW"
      "post_content": {
        "raw": "When you only want certain fields, consider GraphQL.",
        "rendered": "<p>When you only want certain fields, consider GraphQL.</p>\n"
      }
      "some_custom_field": "Oh yeah, custom fields work too"
    },
    {
      "ID": 17,
      "post_title": {
        "raw": "Multiple posts at a time? No problem",
        "rendered": "Multiple posts at a time? No problem"
      "post_content": {
        "raw": "Query posts, terms, comments, and users in a single request using the /any/ endpoint.",
        "rendered": "<p>Query posts, terms, comments, and users in a single request using the /any/ endpoint.</p>\n"
      }
      "some_custom_field": ""
    }
  ]
}
`

Or, if you'd rather make a traditional query, you can use the keyword "query" in your IDs list and then pass a number of "filter" query params to make a query. This works by itself or with additional IDs also specified:

`
// Get posts from author "braad".
/wp-json/graph/v1/posts/query?fields=ID,post_title&filter[author_name]=braad

// Get posts with ID 1 and 2 and up to 5 posts that match a search for "WordPress"
/wp-json/graph/v1/posts/1,2,query?fields=ID,post_title&filter[s]=WordPress&filter[posts_per_page]=5
`

The complete list of allowed filter params can be found below, and there is a filter `simple_graphql_api_allowed_query_args` that lets you control which filter params are allowed.

Currently this plugin supports 3 of the 4 core WordPress resources: **Posts**, **Terms**, and **Comments**. Support for **Users** is planned but Users should really only be accessed with authenticated requests, and right now this plugin only offers a read-only interface (only GET requests), so this will likely come later as part of a larger push to add support for authenticated requests.

Terms and comments can be queried just like posts using the **/terms/** and **/comments/** endpoints:

`
/wp-json/graph/v1/terms/1,3?fields=term_id,name
`

Results in:

`
{
  "terms": [
    {
      "term_id": 1,
      "name": "Category 1"
    },
    {
      "term_id": 3,
      "name": "Category 2"
    }
  ]
}
`

And:

`
/wp-json/graph/v1/comments/1?fields=comment_ID,comment_author,comment_content
`

Results in:

`
{
  "comments": [
    {
      "comment_ID": "1",
      "comment_author": "Mr WordPress",
      "comment_content": {
        "raw": "Hi, this is a comment.\nTo delete a comment, just log in and view the post&#039;s comments. There you will have the option to edit or delete them.",
        "rendered": "<p>Hi, this is a comment.<br />\nTo delete a comment, just log in and view the post&#039;s comments. There you will have the option to edit or delete them.</p>\n"
      }
    }
  ]
}
`

Terms and Comments don't yet support making traditional queries using filter query params, but this is planned for a future version of the plugin.

Querying multiple posts, terms, and comments at a time is alright, but the true power of GraphQL lies in the ability to query across resource types. This plugin implements this ability with the **/any/** endpoint. This endpoint accepts params `posts`, `post_fields`, `terms`, `term_fields`, `comments`, and `comment_fields`, and can be queried like this:

`
/wp-json/graph/v1/any/?posts=1,2&post_fields=ID,post_title,post_content&comments=1&comment_fields=comment_ID,comment_content&terms=3&term_fields=term_id,name
`

Results in:

`
{
  "posts": [
    {
      "ID": 1,
      "post_title": {
        "raw": "Hello world!",
        "rendered": "Hello world!"
      },
      "post_content": {
        "raw": "Welcome to WordPress. This is your first post. Edit or delete it, then start writing!",
        "rendered": "<p>Welcome to WordPress. This is your first post. Edit or delete it, then start writing!</p>\n"
      }
    },
    {
      "ID": 2,
      "post_title": {
        "raw": "Sample Page",
        "rendered": "Sample Page"
      },
      "post_content": {
        "raw": "This is an example page. It's different from a blog post because it will stay in one place and will show up in your site navigation (in most themes). Most people start with an About page that introduces them to potential site visitors. It might say something like this:\n\n<blockquote>Hi there! I'm a bike messenger by day, aspiring actor by night, and this is my website. I live in Los Angeles, have a great dog named Jack, and I like pi&#241;a coladas. (And gettin' caught in the rain.)</blockquote>\n\n...or something like this:\n\n<blockquote>The XYZ Doohickey Company was founded in 1971, and has been providing quality doohickeys to the public ever since. Located in Gotham City, XYZ employs over 2,000 people and does all kinds of awesome things for the Gotham community.</blockquote>\n\nAs a new WordPress user, you should go to <a href=\"http://wp.dev/wp-admin/\">your dashboard</a> to delete this page and create new pages for your content. Have fun!",
        "rendered": "<p>This is an example page. It&#8217;s different from a blog post because it will stay in one place and will show up in your site navigation (in most themes). Most people start with an About page that introduces them to potential site visitors. It might say something like this:</p>\n<blockquote><p>Hi there! I&#8217;m a bike messenger by day, aspiring actor by night, and this is my website. I live in Los Angeles, have a great dog named Jack, and I like pi&#241;a coladas. (And gettin&#8217; caught in the rain.)</p></blockquote>\n<p>&#8230;or something like this:</p>\n<blockquote><p>The XYZ Doohickey Company was founded in 1971, and has been providing quality doohickeys to the public ever since. Located in Gotham City, XYZ employs over 2,000 people and does all kinds of awesome things for the Gotham community.</p></blockquote>\n<p>As a new WordPress user, you should go to <a href=\"http://wp.dev/wp-admin/\">your dashboard</a> to delete this page and create new pages for your content. Have fun!</p>\n"
      }
    }
  ],
  "terms": [
    {
      "term_id": 3,
      "name": "Category 1"
    }
  ],
  "comments": [
    {
      "comment_ID": "1",
      "comment_content": {
        "raw": "Hi, this is a comment.\nTo delete a comment, just log in and view the post&#039;s comments. There you will have the option to edit or delete them.",
        "rendered": "<p>Hi, this is a comment.<br />\nTo delete a comment, just log in and view the post&#039;s comments. There you will have the option to edit or delete them.</p>\n"
      }
    }
  ]
}
`

= Error Handling =

If you pass an ID for any resource that doesn't exist, you'll also get back an `errors` key on the response object. This key will contain an array of any error messages that occured. For example:

`
/wp-json/graph/v1/any/?posts=1,13&post_fields=ID,post_title,post_content&comments=1,5&comment_fields=comment_ID,comment_content
`

Results in:

`
{
  "posts": [
    {
      "ID": 1,
      "post_title": {
        "raw": "Hello world!",
        "rendered": "Hello world!"
      },
      "post_content": {
        "raw": "Welcome to WordPress. This is your first post. Edit or delete it, then start writing!",
        "rendered": "<p>Welcome to WordPress. This is your first post. Edit or delete it, then start writing!</p>\n"
      }
    }
  ],
  "errors": [
    "Post with ID 13 is not published",
    "No comment with ID 5 found"
  ],
  "comments": [
    {
      "comment_ID": "1",
      "comment_content": {
        "raw": "Hi, this is a comment.\nTo delete a comment, just log in and view the post&#039;s comments. There you will have the option to edit or delete them.",
        "rendered": "<p>Hi, this is a comment.<br />\nTo delete a comment, just log in and view the post&#039;s comments. There you will have the option to edit or delete them.</p>\n"
      }
    }
  ]
}
`

Having an errors array in the response like this allows for errors to be returned for any resources in a collection without preventing the API from returning the resources that are valid, but it's a quick solution that is probably not as robust as it needs to be, so expect that this will be improved in a future version.

= Fields =

The fields you can specify are a direct mapping to the fields on the post, term, and comment objects you get when you call `get_post()`, `get_term()`, and `get_comments()` in WordPress. Any fields you ask for that are not valid keys on the post/term/comment object will be treated as meta keys, and any matching meta values will be included in the response. This means that there is no formal distinction between core fields and meta fields. There are only field names, and they either have a matching value or they don't.

Blurring the distinction between core fields and meta fields like this lets you think about custom resource objects in purely abstract terms. From the perspective of the API the basic resource types (Posts, Terms, and Comments) don't have any defined structure other than what you want them to have. You have complete freedom to make use of only the fields you care about and ignore the rest.

It is always recommended, however, to be aware of whether your fields are living on the WordPress resource itself (in the main posts, terms, and comments tables) or in meta (in the postmeta, termmeta, or commentmeta tables). Responses will generally always be faster when less meta is involved, so when designing custom objects it's a good idea to abuse the core fields before abusing meta if possible.

= Meta =

Meta is tricky, so much so that as of February 2016 the core REST API is still working out how to handle meta. At the core of the issue are several things:

* Although WordPress Core does have a formal way to register meta (using `register_meta()`), this function isn't commonly used, and there is no great way to define a meta key as being private vs. public
* All post meta keys that start with an underscore (`_`) are hidden from the default Custom Fields meta box on the post edit screens, but this distinction is mostly about UI rather than truly indicating private vs. public
* PHP can store serialized arrays and objects in the database as meta values, but JSON doesn't distinguish between objects and associative arrays, so when working with the API response on the client side a distinction can't be made, and if you tried to update a serialized object or array over the API then PHP wouldn't be able to distinguish between object and associative array when saving it in the database
* In some cases the default Custom Fields meta box is used by authors to write quick notes or store other internal/private information, and thus exposing this information over the API could be problematic

These issues make handling meta properly very difficult, especially across every WordPress install in the world automatically. Since this plugin offers a read-only interface, the issue of updating meta in the database isn't yet a concern, but exposing private meta unintentionally is still a problem.

I don't have a better answer for this than the team working on the REST API, but I am developing this plugin to be developer friendly and I think meta access is critical, so for now Simple GraphQL API will disallow all meta fields that are prefixed with `_` unless "Safe Meta Mode" is disabled. If Safe Meta Mode is disabled then all meta keys will be accessible over the API, so please only turn Safe Meta Mode off if you know what you are doing. You can turn it off with the `simple_graphql_api_safe_meta_mode` filter like this:

`
add_filter( 'simple_graphql_api_safe_meta_mode', '__return_false' );
`

= Special Keywords =

This plugin includes special keywords **query**, **terms**, **comments**, and **default** that you can pass as field names to support specific use cases.

Although the fundamental query mechanism in Simple GraphQL API revolves around resource IDs, in reality you don't always know the resource IDs you want. The official REST API supports making traditional queries with a series of filter query params, and this plugin supports an identical filter syntax for making traditional queries. Simply pass the **query** keyword in the list of IDs to include a traditional query, and then include the filter params:

`
// Get posts from author "braad".
/wp-json/graph/v1/posts/query?fields=ID,post_title&filter[author_name]=braad

// Get posts with ID 1 and 2 and up to 5 posts that match a search for "WordPress"
/wp-json/graph/v1/posts/1,2,query?fields=ID,post_title&filter[s]=WordPress&filter[posts_per_page]=5
`

The full list of default allowed filter params is:

`
author
author__in
author__not_in
author_name
cat
category__and
category__in
category__not_in
category_name
day
hour
ignore_sticky_posts
m
menu_order
meta_compare
meta_key
meta_value
meta_value_num
minute
monthnum
name
nopaging
offset
order
orderby
p
page
paged
pagename
post__in
post__not_in
post_name__in
post_parent
post_parent__in
post_parent__not_in
post_type
posts_per_page
s
second
tag
tag__and
tag__in
tag__not_in
tag_id
tag_slug__and
tag_slug__in
w
year
`

You can see that almost all types of queries are supported, but some notable params are not supported by default:

`
date_query
fields
has_password
post_password
post_status
perm
meta_query
tax_query
`

The reason why `date_query`, `tax_query`, and `meta_query` are not yet supported is simply because they take array arguments and I'm not yet sure how to handle this in a URL string. The `fields` param doesn't make much sense given that this plugin provides other methods for specifying fields, and `has_password`, `post_password`, `perm` and `post_status` are not supported because only published posts without a password are available over the API.

Naturally the allowed filter params can be customized with the `simple_graphql_api_allowed_query_args` filter, so you can always add these back in.

Another common use case for WordPress sites is getting a collection of posts or a specific post and also wanting the terms and comments associated with that post. It would be a shame to have to make multiple requests to get all of this at once. This plugin supports this use case with the **terms** and **comments** keywords.

To get the term IDs and comment IDs associated with a post you can simply pass "terms" and "comments" as fields you want when querying the `/posts/` endpoint:

`
/wp-json/graph/v1/posts/1?fields=ID,post_title,terms,comments
`

This results in:

`
{
  "posts": [
    {
      "ID": 1,
      "post_title": {
        "raw": "Hello world!",
        "rendered": "Hello world!"
      },
      "terms": "1,4",
      "comments": "1"
    }
  ]
}
`

But what you probably want is to specify the fields you want for the terms and comments also, because just knowing the IDs would still require a second request. You can do this by passing "term_fields" and "comment_fields" query params in the request:

`
/wp-json/graph/v1/posts/1?fields=ID,post_title,terms,comments&term_fields=term_id,name&comment_fields=comment_ID,comment_content
`

This results in:

`
{
  "posts": [
    {
      "ID": 1,
      "post_title": {
        "raw": "Hello world!",
        "rendered": "Hello world!"
      },
      "terms": "1,4",
      "comments": "1"
    }
  ],
  "terms": [
    {
      "term_id": 1,
      "name": "Uncategorized"
    },
    {
      "term_id": 4,
      "name": "Category 2"
    }
  ],
  "comments": [
    {
      "comment_ID": "1",
      "comment_content": {
        "raw": "Hi, this is a comment.\nTo delete a comment, just log in and view the post&#039;s comments. There you will have the option to edit or delete them.",
        "rendered": "<p>Hi, this is a comment.<br />\nTo delete a comment, just log in and view the post&#039;s comments. There you will have the option to edit or delete them.</p>\n"
      }
    }
  ]
}
`

Getting terms along with comments when requesting posts like this allows you to get some very custom responses from the API, and all in a single request and all from the client side. The amount of data you can get in a single request and the fact that it is all customizable (you get only the fields you ask for) from the client side makes this a potentially very powerful solution for building websites and applications.

The **default** keyword can be thought of as a placeholder for a list of fields that you define on the server side using these filters:

`
simple_graphql_api_default_post_fields
simple_graphql_api_default_term_fields
simple_graphql_api_default_comment_fields
`

These filters let you define the core of your custom objects in PHP on the server side and extend them as needed from the client side. All the same field names you would pass into the request as a query param are supported including custom fields. The default fields for each resource out of the box are:

`
Posts: ID, post_title
Terms: term_id, name
Comments: comment_ID, comment_author, comment_content
`

Using the **default** keyword in your request might look like this:

`
/wp-json/graph/v1/posts/1?fields=default,some_custom_field
`

Results in:

`
{
  "posts": [
    {
      "ID": 1,
      "post_title": {
        "raw": "Hello world!",
        "rendered": "Hello world!"
      },
      "some_custom_field": ""
    }
  ]
}
`

You can see that you're not limited to getting the default fields back when you use the **default** keyword. You'll get back the default fields that you specify using the filters *and* you'll get back any additional fields that you pass in on the "fields" query param. The filters get passed the standard REST API request object that contains any additional passed in parameters, allowing you to serve different custom default objects when different additional query params are included.

Building a default object is as simple as:

`
add_filter( 'simple_graphql_api_default_post_fields', 'xxx_custom_default_post_fields', 10, 2 );
/**
 * Customize the fields returned by Simple GraphQL API for Posts when using the 'default' keyword.
 *
 * @param   array   $fields   The Simple GraphQL API default fields.
 * @param   object  $request  The REST API request object.
 * @return  array             The custom default fields.
 */
function xxx_custom_default_post_fields( $fields, $request ) {

  $fields = array(
    'ID',
    'post_title',
    'post_content',
    'post_author',
    'some_custom_field',
  );

  // Check the request for something before adding a field.
  $params = $request->get_params();

  if ( ! empty( $params['something'] ) ) {
    $fields[] = 'some_other_custom_field';
  }

  // You can even include terms and comments by default.
  $fields[] = 'terms';
  $fields[] = 'comments';

  return $fields;
}
`

= Security =

This plugin is currently still a prototype and it has only very simple functionality and almost zero safety as far as exposing sensitive information. From the wp_posts table only published single posts and pages can be accessed, from the wp_comments table only approved comments can be accessed, the `post_password`, `_edit_last`, `_edit_lock`, `comment_author_email`, `comment_author_IP`, `comment_agent`, and `user_id` fields are forcibly removed from the response object, and error handling for some errors is built in, but those are the only safety mechanisms in place so please use at your own risk!

You can use the `simple_graphql_api_private_fields` filter to specifically disallow any core fields or meta keys from the response across all the `/graph/` endpoints, and this is highly recommended if you store sensitive information in meta.

You can use the `simple_graphql_api_post_types` filter to specifically add support for custom post types (only posts and pages are accessible by default).

You can use the `simple_graphql_api_comment_types` filter to specifically add support for custom comment types (only the core comment type, which is actually the absence of a comment type, is accessible by default).

Please be safe when using this plugin, and don't expose more data than you mean to!

= Development Plan =

I'm still learning about GraphQL and I'm using this plugin mostly to experiment. If this plugin proves useful I would love to keep building it out, and if anyone out there finds this kind of thing interesting I'd love to work together.

The first release of this plugin is version 0.8.0, with the intention that although the plugin is generally stable and ready to be used, there may be **breaking changes** made before the plugin hits 1.0. I wanted to get the plugin out there and get feedback on it early, but I know it still has a lot of room for improvement. Please help me make it better by letting me know if you find it useful and what if any changes you'd like to see. The plugin is on [on Github](https://github.com/BraadMartin/simple-graphql-api "Simple GraphQL API for WordPress") and issue filing and pull requests are always welcome. :)

== Installation ==

= Manual Installation =

1. Upload the entire `/simple-graphql-api` directory to the `/wp-content/plugins/` directory.
1. Activate 'Simple GraphQL API' through the 'Plugins' menu in WordPress.

= Better Installation =

1. Go to Plugins > Add New in your WordPress admin and search for 'Simple GraphQL API'.
1. Click Install.

== Frequently Asked Questions ==

= When does the plugin load? =

The plugin loads on `rest_api_init` at the default priority (10).

= Can the URL base for the API be customized? =

Yes! Use the `simple_graphql_api_url_base` filter to customize the URL:

`
add_action( 'simple_graphql_api_url_base', 'xxx_custom_api_url_base' );
/**
 * Use a custom URL base for Simple GraphQL API.
 *
 * @param   string  $base  The default URL base.
 * @return  string         The custom URL base.
 */
function xxx_custom_api_url_base( $base ) {

  $base = 'custom-api/v1';

  return $base;
}
`

Then your requests might look like: `/wp-json/custom-api/v1/posts/1?fields=default`

= What fields are specifically disallowed by this plugin? =

The following fields are specifically disallowed:

`
post_password
_edit_last
_edit_lock
comment_author_email
comment_author_IP
comment_agent
user_id
`

You can use the `simple_graphql_api_private_fields` filter to customize which fields are disallowed.

== Changelog ==

= 0.8.0 =
* First Release

== Upgrade Notice ==

= 0.8.0 =
* First Release