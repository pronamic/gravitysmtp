# Project Hermes

__A Query Architecture for dynamic, performant, client-controlled data handling and processing.__

## AJAX, GraphQL, and The Problem of Query Structures

In our typical product configuration, bespoke endpoints are setup and maintained for each unique type
of query that might come from the Client. Need to get a list of users? That's an endpoint. Get the companies users
belong to? Another endpoint.
Save a setting, get a setting, update a field, get a field - all individual endpoints.

Oftentimes, this paradigm is more than acceptable. The total number of query types being handled is typically low, and
the
need for net-new endpoints rarely occurs.

But what about situations in which there are many, many types of data to query, in all sorts of various configurations?
What if the
application is a single-page React app, where all the data comes view queries and nothing exists on page load? What if a
given data type
or object model requires a new piece of data? Suddenly we're not just spinning up unique endpoints for each situation,
but also having to
update/modify/drop database tables as well (something that has caused countless headaches for Plugin products like
ours).

Enter GraphQL, an alternative Query standard to REST/AJAX. GraphQL differs from traditional REST architecture primarily
in how
query structures and data shapes are controlled. In REST, you retrieve a specific object from a specific endpoint. "
Users" are queried via "/json/users".
"Companies" are queried via "json/companies". If you need both, or need to get Users categorized by a given Company,
it's two separate queries,
while necessitating that values from the first are saved and passed to the second. In simple contexts, this is fine, but
as complexity grows,
so do the headaches around this.

GraphQL tackles this by having the Server simply establish a structured schema of Object Types, along with their
relationships, fields, and other criteria. The Client can then use this schema to query *anything it needs*, all at a
single endpoint.

## That's great, but what are you talking about?

Let's look at a typical example: retrieving a list of companies, the departments at said company, and the employees
assigned to each department. In REST, that
would be 3 separate requests, like the following pseudo-code:

```js
const companies = request( { dataType: 'company' } );

for ( company in companies ) {
	const departments = request( { dataType: 'department', companyId: company.id } );

	for ( department in departments ) {
		const employees = request( { dataType: 'employee', departmentId: department.id } );
		department.employees = employees;
	}

	company.departments = departments;
}

return companies;
```

Not terrible, but if more nesting/relationships were required, things would quickly balloon out of control.
Not to mention we're making 3 distinct requests to the server, requiring more time and bandwidth.

Now let's look at how this would work in GraphQL:

```js
const query = `company {
                    id,
                    name,
                    address,
                    department {
                        id,
                        name,
                        employeeNames: employee {
                            id,
                            first_name,
                            last_name
                        }
                    }
                }`;

const companies = request( { query } );
```

Not only do we reduce the total number of requests to one, we also have
complete, full control over the data shape returned to us. We can even alias the various
field values (like we do here by aliasing `employee` to `employeeNames` ). This results in substantially
decreased overhead for the server, as new data structures don't require any updates. It also makes architecting
the client-side system easier, as dynamic queries can be made simply by traversing the schema provided
by the server and selecting which fields/related objects to query.

Magic!

## Monkey wrench: GraphQL is enormé.

This is all good and well, but how do we take advantage of GraphQL? On the plus side, it's an open-source
system that is actively-maintained and provided by some incredible engineers at Facebook/Meta.

Unfortunately, it's also absolutely gigantic. Including the library required to run a functioning GraphQL server
in PHP is dozens of additional MB of file bloat, all replete with opportunities for conflicts with other plugins
which may use GraphQL. Includig it in a distributed plugin is a non-starter, full-stop.

So are we out of luck?

## Solution: just write our own

Given the limited file size constraints and variety in hosting platforms, we decided the best approach
would be to adopt GraphQL's syntax and functionality, but hand-roll our own server for parsing GraphQL
queries into SQL statements that we can execute against the database.

And thus, Project Hermes was born!

## The Bits and Bobs

Fundamentally, Hermes consists of four mechanisms: `Models`, `Handlers`, `Tokens`, and `Runners`.
During a given request, the `Models` registered to the system are referenced by a `Handler`, which takes the Query
String
and lexes the string into defined `Tokens`, each of which are classes holding the
structured data needed for the various `Runners` to execute the query.

That's a lot, but let's look at them one by one:

### Models: The Backbone of Hermes Data

`Models` are responsible for describing all of the various object types available for
querying and mutation. A given `Model` defines:

- The fields available for the object type
- The meta fields available to assign to the object type
- The minimum Capability required for they querying user to access the object type
- The relationships a given object type has to _other_ object types.

When a query is executed, the registered `Models` are referenced to ensure the data being
requested exists, that the data is available to the user, and the various tables required
for querying.

Consider the following:

```php
class FakeContactModel extends \Gravity_Forms\Gravity_Tools\Hermes\Models\Model {

	protected $type = 'contact';

	protected $access_cap = 'manage_options';

	public function fields() {
		return array(
			'id'         => Field_Type_Validation_Enum::INT,
			'first_name' => Field_Type_Validation_Enum::STRING,
			'last_name'  => Field_Type_Validation_Enum::STRING,
			'email'      => Field_Type_Validation_Enum::EMAIL,
			'phone'      => Field_Type_Validation_Enum::STRING,
			'foobar'     => function ( $value ) {
				if ( $value === 'foo' ) {
					return 'foo';
				}

				return null;
			},
		);
	}

	public function meta_fields() {
		return array(
			'secondary_phone'   => Field_Type_Validation_Enum::STRING,
			'alternate_website' => Field_Type_Validation_Enum::STRING,
		);
	}

	public function relationships() {
		return new \Gravity_Forms\Gravity_Tools\Hermes\Utils\Relationship_Collection(
			array(
				new \Gravity_Forms\Gravity_Tools\Hermes\Utils\Relationship( 'group', 'contact', 'manage_options', true )
			)
		);
	}

}
```

This code would register a new `Model` for an object type named `contact`. It defines several fields
that a `contact` can have, as well as any `meta` fields that might be assignable to it. The `fields`
should exist in the related DB table as columns, while `meta` fields can be added ad-hoc and are stored
in the separate `meta` lookup table.

Each field, whether meta or local, references a specific Field Validation Type, either by directly referencing
one of the Field Validation Type Enum values, or by providing a custom callback. This determines how values
will be validated and sanitized before being inserted into the database.

Finally, it establishes a relationship to another `Model`, `Group`. Since the table connects Groups _to_ Contacts,
this `Model` designates the relationship as `reversed`, telling the system to look in the `group_contact` table.

With this `Model` in-hand, you can register it to a `Model_Collection` by simply using:

```php
$contact    = new FakeContactModel();
$collection = new Model_Collection();

$collection->add( 'contact', $contact );
```

### Handlers and Tokens: Hermes' traffic coordinator

When a Query is sent to the system (typically through a registered AJAX endpoint or REST route), it's passed to a
`Handler`, either the `Query_Handler` for _query_ calls or
the `Mutation_Handler` for mutation calls (Insert, Update, Delete, Connect).

This `Handler` then kicks off the process by passing the Query string to a set of `Tokens`, each of which take
the string (or portions of the string) and lex it into usable objects (or `Tokens`) holding all of the data needed
for the specific operation being handled.

To accomplish this, each `Token` defines a series of key/value pairs, where the `key` represents a regex `MARK` for
identifying the
path taken in the regular expression, and the `value` is the pattern to match against while running the expression.
These are passed to a call to
`preg_match_all()` against the Query string.

This results in an array of `MARK` designators along with a matching array containing the matched results. These pairs
are evaluated
and either stored to the `Token` as data or (when the `Token` is identified as requiring further lexing) passed along to
another `Token` for
processing.

### Runners: Hermes' Bravest Little Soldiers

Once all the various `Tokens` have been generated, we're left with a structured set of PHP Objects, each containing the
data
necessary for performing the Query. With these in hand, the system then passes them on to a series of `Runners`. These
clases
are responsible for processing the stack of `Tokens`, taking their various bits of data, and converting it into SQL
statements
which can then be executed against the Databse.

Once these SQL statements are executed, the results are collated and - when applicable - the structure outlined
in the original Query request is returned. (Note: Delete and Connect mutations don't have a bespoke return structure.
The response from
such operations will be consistent).

## Implementing Hermes in New Projects

Let's go over a sample of how to implement Hermes in a new project. This example will assume that you are
using the standard Service Provider pattern we use in other products, but if not you simple need to move the
code to whatever bootstrapping/provider paradigm you are using.

### Setting up Models

First, you need to register a `Model_Collection` to your provider and add some `Models` to it based on what your
data needs are. We'll register a single `Contact` model.

```php

$container->add( self::CONTACT_MODEL, function() {
    // Assume this Model exists elsewhere in the codebase.
    return new Contact_Model;
});

$container->add( self::MODEL_COLLECTION, function() use ( $container ) {
    $collection = new Model_Collection();
    $collection->add( 'contact', $container->get( self::CONTACT_MODEL ) );
});
```

### Configuring the Handlers

Next, we need to configure the `Handlers` for use in this system. To do this,
we'll need a couple pieces: a string representing the `db_namespace` used in this product,
and the `Model_Collection` we registered earlier. The `db_namespace` should be unique to
the product, and is used when generating table names.

```php
$container->add( self::QUERY_HANDLER, function() use ( $container ) {
    $db_namespace = 'gravitytools';
    $model_collection = $container->get( self::MODEL_COLLECTION );
    return new Query_Handler( $db_namespace, $model_collection );
});
```

The `Mutation_Handler` is a bit more involved, as we'll also need to determine
which Mutation types we want to support, and pass the related `Runners` to our `Handler`.

Typically you'll need to support Insert, Update, Connect, and Delete operations.

```php

$container->add( self::MUTATION_HANDLER, function() use ( $container ) {
    $db_namespace = 'gravitytools';
    $model_collection = $container->get( self::MODEL_COLLECTION );
    $query_handler = $container->get( self::QUERY_HANDLER );
    $runners = array(
        'insert'  => new Insert_Runner( $db_namespace, $query_handler ),
        'delete'  => new Delete_Runner( $db_namespace, $query_handler ),
        'connect' => new Connect_Runner( $db_namespace, $query_handler ),
        'update'  => new Update_Runner( $db_namespace, $query_handler ),
	);
    return new Mutation_Handler( $db_namespace, $model_collection, $query_handler, $runners );
});
```

With that, the system now has all the information it needs to start handling Queries!

### Adding Endpoints

Finally, you'll need to add endpoints to handle the Query requests. Typically you'll want to
register 2 endpoints, one for Query and one for Mutation, but depending on your needs you may also
use a single endpoint and handle routing internally. For this example, we'll register 2 endpoints via
WordPress's AJAX system.

```php
add_action( 'wp_ajax_hermes_query', function() use ( $container ) {
    $query_string = filter_input( INPUT_POST, 'query', FILTER_DEFAULT );
    
    // The handlers call wp_send_json() at the end, so no exit is required.
    $container->get( self::QUERY_HANDLER )->handle_query( $query_string );
} );

add_action( 'wp_ajax_hermes_mutation', function() use ( $container ) {
    $query_string = filter_input( INPUT_POST, 'mutation', FILTER_DEFAULT );
    
    // The handlers call wp_send_json() at the end, so no exit is required.
    $container->get( self::MUTATION_HANDLER )->handle_mutation( $query_string );
} );

```

With that in place, `POST` requests to `/wp-admin/admin-ajax.php?action=hermes_query` will trigger a Query 
request, and `POST` requests to `/wp-admin/admin-ajax.php?action=hermes_mutation` will trigger a Mutation.

In actual production environments, you'll want to make sure to add in authentication via nonces/keys/some 
other mechanism, but at this point you have a functioning Hermes system!

## Database Table Setup

In the future, the sytem will reference any registered `Models` and automatically generate DB tables based 
on their information. For now, however, the table setup is a manual process. 

The most-important aspect of table setup is that you follow the explicit naming convention required. The convention
is as follows:

#### For Objects
`$wpdb->prefix + '_' + $db_namespace + '_' + $object_type` 

An object type named `contact` in a product with a namespace of `gravitytools` will need a table named `wp_gravitytools_contact`.

#### For Lookup Tables
`$wpdb->prefix + '_' + $db_namespace + '_' + $from_object_type + '_' + $to_object_type`

A table connecting a `contact` to `group` in a product with a namespace of `gravitytools` will need a table named `wp_gravitytools_contact_group`.

Columns should be `id`, `$from_object_id`, and `$to_object_id`.

So our `contact` to `group` table would have columns of `id`, `contact_id`, and `group_id`.

#### For the Meta Table
`$wpdb->prefix + '_' + $db_namespace + '_' + 'meta` 

A meta table in a product with a namespace of `gravitytools` will need a table named `wp_gravitytools_meta`.

## Using Hermes

Once you've gotten Hermes set up in your environment (and created the related DB tables), you're ready to start
querying it. 

There are two fundamental types of interactions you can have with Hermes: `query` and `mutation`. 

### Queries

Queries are the mechanism used to _retrieve_ data from the server. In a REST paradigm, this would be your
`GET` requests. They don't modify any existing data, they simply retrieve data from the server in the shape 
sent in the query.

#### Basic Query

Let's look at a very basic example. Imagine you need to retrieve all of the `company` records from the server,
and that you want to have the `id`, `name`, and `address` fields returned for each record. The query would look
something like this:

```graphql
{
    company {
        id,
        name,
        address
    }
}
```

#### Adding Arguments

That works great, but it's rare that you need to retrieve every single record of a given type in a single query. 
Instead, let's add `limit` as an `argument` so we only retreive the first 10 records:

```graphql
{
    company( limit: 10 ) {
        id,
        name,
        address
    }
}
```

`Arguments` can be combined by passing multiple comma-delineated pairs:

```graphql
{
    company( limit: 10, offset: 5, is_operational: true ) {
        id,
        name,
        address
    }
}
```

Here we provided three `arguments`: we limite the results to 10, offset the results by 5, and restrict 
the results to only those records in which the field `is_operational` is `true`.

#### Argument Operators

By default, an argument will be evaluated as `=` (e.g. `limit: 10` becomes `limit = 10` ). Other operator types
can be indicated by suffixing the argument's field name with the operator type you wish to use. See the following table
for all the supported operators.

| Operator Type | Suffix      | Example                   |
|---------------|-------------|---------------------------|
| `=`           | `No Suffix` | `length: 10`              |
| `!=`          | `_ne`       | `length_ne: 10`           |
| `>`           | `_gt`       | `length_gt: 10`           |
| `<`           | `_lt`       | `length_lt: 10`           |
| `>=`          | `_gte`      | `length_gte: 10`          |
| `<=`          | `_lte`      | `length_lte: 10`          |
| `IN/CONTAINS` | `_in`       | `length_in: 10\|20\|30\|` |


#### Related Object Records

We can also query for records related to the main object type we're retrieving. For instance, if we wanted to 
retrieve all of the `departments` in each `company`, we can simply add it like so:

```graphql
{
    company( limit: 10 ) {
        id,
        name,
        address,
        department {
            id,
            name,
        }
    }
}
```

Queries can be infinitely-nested, and each level can utilize arguments and fields just like a non-nested query. Do note, however, 
that higher levels of nesting will result in more-complex queries, and on larger datasets the query time can balloon. Using `limits` and 
other `argument` types can help alleviate this, but always use caution when heavily-nesting your queries.

#### Aliasing

Sometimes, the field names we use in the database don't work particularly well in client applications. Rather than forcing 
the client application to traverse the resulting records and rename fields as needed, we can simply alias the field names directly 
in the query:

```graphql
{
    first_ten_companies: company( limit: 10 ) {
        id,
        business_name: name,
        address
    }
}
```

This would alias `company` to `first_ten_companies`, and the field `name` to `business_name` in the resulting data. Any level of the query
can be aliased, whether it's a field or a nested query.

### Mutations

In addition to retrieving data, we can also use Hermes to modify, create, or remove existing data. These types of operations 
are called `mutations`, and come in four varieties. The type of mutation can be defined by prefixing the `object type` with the type
of `mutation` you wish to run.

#### Insert

An `insert` mutation inserts a new record into the database. It consists of two parts: the objects to insert, and the values to return once 
they have been inserted:

```graphql
{
    insert_company( objects: [{ name: "My Business", address: "1234 Pine Drive" }]) {
        returning {
            id,
            name,
            address
        }
    }
}
```

The above `mutation` would result in our record being added to the database, and the `id`, `name`, and `address` fields being returned for our new record.

Multiple records can be inserted at a time, and the returned values will include every record created.

#### Update

An `update` mutation modifies an existing record with new values. Similar to the `insert` mutation, we also define what data shape we want to be returned
once the update is complete. Unlike `insert` mutations, only a single record can be updated at a time.

```graphql
{
    update_company( id: 5, name: "My Updated Company Name" ){
        returning {
            id,
            name,
            address
        }
    }
}
```

The above mutation would update the record with an `id` of `5` with the new name `My Updated Company Name`.

**Note**: every `update` mutation _must_ include an `id` so that the server knows which record to update. Updates 
based on other column values are not currently supported.

#### Delete

A `delete` mutation simply removes the provided record from the database. No returning data shape is defined, as there 
is no data to return.

```graphql
{
    delete_company( id: 10 ){}
}
```

The above `mutation` would result in the company record with an id of `10` being deleted from the database. 

**Note**: as with `update` mutations, you must pass the `id` of the record you wish to delete.

#### Connect

A `connect` mutation creates a relationship between two `object types` (if such a relationship is defined in
the appropriate object `models`). We don't define a resulting data shape; instead the server simply returns a 
basic success/failure response.

```graphql
{
    connect_company_department( from : 1, to: 3 ){}
}
```

The above `mutation` would result in the `company` with an `id` of `1` to be related to the `department` with 
an `id` of `3`.