# GraphQL API #
**Contributors:** Braad  
**Donate link:** http://braadmartin.com/  
**Tags:** graphql, graph, api, rest  
**Requires at least:** 4.4  
**Tested up to:** 4.5  
**Stable tag:** 1.0.0  
**License:** GPLv3  
**License URI:** http://www.gnu.org/licenses/gpl-3.0.html  

Adds a GraphQL-style read-only interface for interacting with the REST API.

## Description ##

Normally with the WordPress REST API and REST APIs in general, you are interacting with a complete object representation of a resource. You ask for a post at `/wp-json/wp/v2/post/13` and you get back a JSON object representation of that post, with all of the standard fields that you would expect. You don't have to specify which fields you get back, you just get back a complete set of fields that represent that resource.

GraphQL is a query language developed by Facebook that offers a slightly different way of asking for and receiving data from a resource. With GraphQL, you pass in an object with only the field keys you want, and the API fills up the object and passes it back to you. This moves the ability to include things like custom fields (post meta) to the client, and it makes the response you get more predictable and specific to your use case.

The concept of only getting back specific fields you ask for is potentially very powerful. This plugin implements this concept by letting you pass a query param "fields" to an endpoint `/wp-json/graph/v1/post/{:id}` consisting of a comma separated list of the fields you want, and you'll get only those fields back.

In other words, you send a GET request like this:


	/wp-json/graph/v1/post/13?fields=ID,post_title,post_content,some_custom_field


and you get back:


	{
	  "ID": 13,
	  "post_title": "GraphQL FTW",
	  "post_content": "When you only want certain fields, consider GraphQL.",
	  "some_custom_field": "Oh yeah, custom fields work too"
	}


Here is a prettier way to send the request using jQuery's `$.ajax()` that is also supported:


	$.ajax({
		url: 'http://wp.dev/wp-json/graph/v1/post/1', // Your URL goes here
		type: 'get',
		data: {
			fields: [
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


The fields you can specify are a direct mapping to the fields on the post object when you make a standard query in WordPress. Any fields you ask for that are not valid keys on the post object will be treated as post meta keys. This allows you to ask for the custom field values you want from the client side without having to filter them into the response on the server side.

At this point this plugin is just a prototype and it has very little functionality and almost zero safety as far as exposing sensitive information. Only published single posts can be accessed, the post_password field is forcibly removed from the response object, and error handling for some errors is built in, but those are the only safety mechanisms in place so use at your own risk!

I'm still learning about GraphQL and I'm using this plugin mostly to experiment. If this plugin proves useful I would love to keep building it out and add support for interacting with the other resources besides posts (Users, Terms, and Comments) and eventually add support for making authenticated requests to actually modify resources with PUT, POST, and DELETE requests.

If anyone out there finds this kind of thing interesting I'd love to work together. The plugin is on [on Github](https://github.com/BraadMartin/graphql-api "GraphQL API for WordPress") and issue filing and pull requests are always welcome. :)

## Installation ##

### Manual Installation ###

1. Upload the entire `/graphql-api` directory to the `/wp-content/plugins/` directory.
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