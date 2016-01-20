=== Simple GraphQL API ===
Contributors: Braad
Donate link: http://braadmartin.com/
Tags: graphql, graph, api, simple, rest
Requires at least: 4.4
Tested up to: 4.5
Stable tag: 1.0.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

A simple read-only interface for making GraphQL-style queries with the REST API. Supports querying multiple resources across multiple resource types in a single request.

== Description ==

Normally with the WordPress REST API and REST APIs in general, you are interacting with a complete representation of a resource object. You ask for a post at `/wp-json/wp/v2/post/13` and you get back a JSON object representation of that post, with all of the standard fields that you would expect. You don't have to specify which fields you get back, you just get back a complete set of fields that represent that resource.

GraphQL is a query language developed by Facebook that offers a different way of asking for and receiving data from a resource. With GraphQL, you pass in an object with only the field keys you want, and the API fills up the object and passes it back to you. In the context of WordPress resources (posts, terms, comments, and users), this moves the ability to include things like custom fields (post/term/comment/user meta) to the client, and it makes the response you get more predictable and specific to your use case.

The concept of only getting back specific fields you ask for is very powerful. This plugin implements this concept by letting you pass multiple ids and the query param "fields" to endpoints for the four core resources, or query an /any/ endpoint that allows you to query multiple resources across resource types in a single request.

To query the posts endpoint send a request to `/wp-json/graph/v1/posts/{:ids}?fields=xxx,xxx` where `{:ids}` is a comma separated list of the post ids you want and `xxx,xxx` is a comma separated list of the fields you want, and you'll get only those fields back.

In other words, you send a GET request like this:

`
/wp-json/graph/v1/posts/13,17?fields=ID,post_title,post_content,some_custom_field
`

and you get back:

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

Here is a prettier way to send the request using jQuery's `$.ajax()` that is also supported:

`
$.ajax({
	url: 'http://wp.dev/wp-json/graph/v1/posts/13,17', // Your URL goes here.
	type: 'get',
	data: {
		fields: [ // Your fields go here.
			'ID',
			'post_title',
			'post_content',
			'some_custom_field'
		]
	},
	success: function( response ) {
		console.log( response ); // Your object will be here.
	}
});
`

As of 1/19/16, 3 of the 4 core WordPress objects are supported, including Posts, Terms, and Comments. Support for Users is planned, but currently the plugin only provides read-only access and supports unauthenticated requests, and Users should really only be accessed via authenticated requests, so this will likely come later as part of a larger support for making authenticated requests.

Terms and comments can be queried just like posts:

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

Querying multiple posts, terms, and comments at a time is alright, but the true power of GraphQL lies in the ability to query across resource types. This plugin implements this ability with the /any/ endpoint. This endpoint accepts params `posts`, `post_fields`, `terms`, `term_fields`, `comments`, and `comment_fields`, and can be queried like this:

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

The fields you can specify are a direct mapping to the fields on the post, term, and comment objects you get when you call `get_post()`, `get_term()`, and `get_comments()` in WordPress. Any fields you ask for that are not valid keys on the post/term/comment object will be treated as meta keys, and any matching meta values will be included in the response. This allows you to ask for the custom field values you want from the client side without having to filter them into the response on the server side, which shifts more of the logic for your application from the server to the client.

At this point this plugin is just a prototype and it has very little functionality and almost zero safety as far as exposing sensitive information. From the wp_posts table only published single posts and pages can be accessed, the post_password, comment_author_email, comment_author_IP, and comment_agent fields are forcibly removed from the response object, and error handling for some errors is built in, but those are the only safety mechanisms in place so use at your own risk!

I'm still learning about GraphQL and I'm using this plugin mostly to experiment. If this plugin proves useful I would love to keep building it out and add support for making authenticated requests to actually modify resources with PUT, POST, and DELETE requests.

If anyone out there finds this kind of thing interesting I'd love to work together. The plugin is on [on Github](https://github.com/BraadMartin/simple-graphql-api "GraphQL API for WordPress") and issue filing and pull requests are always welcome. :)

== Installation ==

= Manual Installation =

1. Upload the entire `/simple-graphql-api` directory to the `/wp-content/plugins/` directory.
1. Activate 'GraphQL API' through the 'Plugins' menu in WordPress.

= Better Installation =

1. Go to Plugins > Add New in your WordPress admin and search for 'GraphQL API'.
1. Click Install.

== Frequently Asked Questions ==

= When does the plugin load? =

The plugin loads on `rest_api_init` at the default priority (10).

== Changelog ==

= 1.0.0 =
* First Release

== Upgrade Notice ==

= 1.0.0 =
* First Release