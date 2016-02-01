# Simple GraphQL API #
**Contributors:** Braad  
**Donate link:** http://braadmartin.com/  
**Tags:** graphql, graph, api, simple, rest  
**Requires at least:** 4.4  
**Tested up to:** 4.5  
**Stable tag:** 1.0.0  
**License:** GPLv3  
**License URI:** http://www.gnu.org/licenses/gpl-3.0.html  

A simple read-only interface for making GraphQL-style queries with the REST API. Supports querying multiple resources across multiple resource types in a single request.

## Description ##

Normally with the WordPress REST API and REST APIs in general, you are interacting with a complete object representation of a resource. You ask for a post at `/wp-json/wp/v2/post/13` and you get back a JSON object representation of that post, with all of the standard fields that you would expect. You don't have to specify which fields you get back, you just get back a complete set of fields that represent the current "state" of that resource.

GraphQL is a query language/API interface developed by Facebook that offers a different way of asking for and receiving data from a resource. With GraphQL you pass in an object with only the field keys you want, and the API fills up the object and passes it back to you. This allows you to define the object you want to work with from the client side, and simply let the API do the work of filling up the fields you ask for.

In the context of WordPress resources (posts, terms, comments, and users), there are always the core fields plus any meta fields on each of the 4 main resources. When using the core REST API endpoints you would register any extra custom fields (meta) you want in the response on the server side in PHP, and this is easily done and works great for many use cases. This allows you to define the object you want to work with on the server side by adding fields, but you would never want to remove any of the core fields from the response because this would be disruptive to any other applications trying to connect to the site over the REST API and expecting certain fields to be there, so there is always a minimum of data you'll get back in the response.

This plugin is an experiment that takes a more GraphQL-style approach. It lets you ask for specific fields on the core resources without regard for whether live in the primary table (posts, terms, comments, users) or in the meta tables (postmeta, termmeta, commentmeta, usermeta), and without requiring any prior field registration on the server side. This allows you to define the object you want to work with on the client side, it makes the response more predictable and specific to your use case, and it naturally leads to smaller responses, all while taking advantage of the same object caching used by the REST API.

Simply pass one or multiple resource ids and the query param "fields" to endpoints for posts, terms, and comments, or send special query params to the /any/ endpoint to query multiple resources across multiple resource types in a single request, and you'll get back exactly what you ask for. The endpoints supported by this plugin include:


	/wp-json/graph/v1/posts/{:ids}?fields=xxx,xxx
	
	/wp-json/graph/v1/terms/{:ids}?fields=xxx,xxx
	
	/wp-json/graph/v1/comments/{:ids}?fields=xxx,xxx
	
	/wp-json/graph/v1/any/?posts={:ids}&post_fields=xxx,xxx&terms={:ids}&term_fields=xxx,xxx&comments={:ids}&comment_fields=xxx,xxx


To query the **/posts/** endpoint send a request to `/wp-json/graph/v1/posts/{:ids}?fields=xxx,xxx` where `{:ids}` is a comma separated list of the post ids you want and `xxx,xxx` is a comma separated list of the fields you want, and you'll get only those fields back.

Request:


	/wp-json/graph/v1/posts/13,17?fields=ID,post_title,post_content,some_custom_field


Response:


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


Here is a prettier way to send the request using jQuery's `$.ajax()` that is also supported:


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


As of 2/1/16 this plugin supports 3 of the 4 core WordPress resources: **Posts**, **Terms**, and **Comments**. Support for **Users** is planned but Users should really only be accessed with authenticated requests, and right now this plugin only offers a read-only interface (only GET requests), so this will likely come later as part of a larger push to add support for authenticated requests.

Terms and comments can be queried just like posts:


	/wp-json/graph/v1/terms/1,3?fields=term_id,name


Results in:


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


And:


	/wp-json/graph/v1/comments/1?fields=comment_ID,comment_author,comment_content


Results in:


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


Querying multiple posts, terms, and comments at a time is alright, but the true power of GraphQL lies in the ability to query across resource types. This plugin implements this ability with the **/any/** endpoint. This endpoint accepts params `posts`, `post_fields`, `terms`, `term_fields`, `comments`, and `comment_fields`, and can be queried like this:


	/wp-json/graph/v1/any/?posts=1,2&post_fields=ID,post_title,post_content&comments=1&comment_fields=comment_ID,comment_content&terms=3&term_fields=term_id,name


Results in:


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


If you pass an ID for any resource that doesn't exist, you'll also get back an `errors` key on the response object. This key will contain an array of any error messages that occured. For example:


	/wp-json/graph/v1/any/?posts=1,13&post_fields=ID,post_title,post_content&comments=1,5&comment_fields=comment_ID,comment_content


Results in:


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


The fields you can specify are a direct mapping to the fields on the post, term, and comment objects you get when you call `get_post()`, `get_term()`, and `get_comments()` in WordPress. Any fields you ask for that are not valid keys on the post/term/comment object will be treated as meta keys, and any matching meta values will be included in the response.

You may have noticed that the fundamental query mechanism revolves around resource IDs, but the most common use case for most WordPress sites is getting a collection of posts or a specific post and also the terms and comments associated with that post, and it would be a shame to have to make multiple requests to get all of this at once. This plugin supports this specific use case in a couple of ways. To get the term IDs and comment IDs associated with a post you can simply pass "terms" and "comments" as fields you want when querying the /posts/ endpoint:


	/wp-json/graph/v1/posts/1?fields=ID,post_title,terms,comments


Results in:


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


But what you probably want is to specify the fields you want for the terms and comments also, because just knowing the IDs would still require a second request. You can do this by passing "term_fields" and "comment_fields" query params in the request:


	/wp-json/graph/v1/posts/1?fields=ID,post_title,terms,comments&term_fields=term_id,name&comment_fields=comment_ID,comment_content


Results in:


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


Getting terms along with comments when requesting posts like this allows you to get some very custom responses from the API, get everything you want in a single request, and do all of this from the client side. The amount of data you can get in a single request, and the fact that it is all customizable (you get only the fields you ask for) makes this a potentially very powerful solution for building websites and applications on top of the API.

At this point this plugin is just a prototype and it has only very simple functionality and almost zero safety as far as exposing sensitive information. From the wp_posts table only published single posts and pages can be accessed, from the wp_comments table only approved comments can be accessed, the post_password, comment_author_email, comment_author_IP, and comment_agent fields are forcibly removed from the response object, and error handling for some errors is built in, but those are the only safety mechanisms in place so please use at your own risk! You can use the `simple_graphql_api_private_fields` filter to specifically disallow any core fields or meta keys from the response across all the /graph/ endpoints, and this is highly recommended if you store sensitive information in meta.

I'm still learning about GraphQL and I'm using this plugin mostly to experiment. If this plugin proves useful I would love to keep building it out and add support for interacting with User resources and making authenticated requests to actually modify resources with PUT, POST, and DELETE requests.

If anyone out there finds this kind of thing interesting I'd love to work together. The plugin is on [on Github](https://github.com/BraadMartin/simple-graphql-api "GraphQL API for WordPress") and issue filing and pull requests are always welcome. :)

## Installation ##

### Manual Installation ###

1. Upload the entire `/simple-graphql-api` directory to the `/wp-content/plugins/` directory.
1. Activate 'GraphQL API' through the 'Plugins' menu in WordPress.

### Better Installation ###

1. Go to Plugins > Add New in your WordPress admin and search for 'GraphQL API'.
1. Click Install.

## Frequently Asked Questions ##

### When does the plugin load? ###

The plugin loads on `rest_api_init` at the default priority (10).

## Changelog ##

### 1.0.0 ###
* First Release

## Upgrade Notice ##

### 1.0.0 ###
* First Release