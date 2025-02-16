Redis clients
====================

This package provides support for three different Redis clients.

  * PhpRedis
  * Predis
  * Relay (See configuration recommendations for in-memory cache)

By default, the first available client will be used in that order, to configure
it explicitly, use

    $settings['redis.connection']['interface'] = 'PhpRedis';

Each supported client has its own README client specific installation and
configuration options.

Common cache configuration
===================

See settings.redis.example.php for a quick start and recommended configuration.

Customize the default host and port:

    $settings['redis.connection']['host'] = '127.0.0.1';
    $settings['redis.connection']['port'] = 6379;

Use Redis for all caches:

    $settings['cache']['default'] = 'cache.backend.redis';

Configure usage for a specific bin

    $settings['cache']['bins']['render'] = 'cache.backend.redis';

The example.services.yml from the module will replace the cache tags checksum
service, flood and the lock backends (check the file for the current list).
Either include it directly or copy the desired service definitions into a site
specific services.yml file for more control.

    $settings['container_yamls'][] = 'modules/contrib/redis/example.services.yml';

It is recommended to enable the redis module, to use the report feature, but
the redis.services.yml can also be included explicitly.

    $settings['container_yamls'][] = 'modules/contrib/redis/redis.services.yml';

Compressing the data stored in redis can massively reduce the needed storage.

To enable, set the minimal length after which the cached data should be
compressed:

    $settings['redis_compress_length'] = 100;

By default, compression level 1 is used, which provides considerable storage
optimization with minimal CPU overhead, to change:

    $settings['redis_compress_level'] = 6;

Redis can also be used for the container cache bin, the bootstrap container
needs to be configured for that.

    $class_loader->addPsr4('Drupal\\redis\\', 'modules/contrib/redis/src');

    $settings['bootstrap_container_definition'] = [
      'parameters' => [],
      'services' => [
        'redis.factory' => [
          'class' => 'Drupal\redis\ClientFactory',
        ],
        'cache.backend.redis' => [
          'class' => 'Drupal\redis\Cache\CacheBackendFactory',
          'arguments' => ['@redis.factory', '@cache_tags_provider.container', '@serialization.phpserialize'],
        ],
        'cache.container' => [
          'class' => '\Drupal\redis\Cache\PhpRedis',
          'factory' => ['@cache.backend.redis', 'get'],
          'arguments' => ['container'],
        ],
        'cache_tags_provider.container' => [
          'class' => 'Drupal\redis\Cache\RedisCacheTagsChecksum',
          'arguments' => ['@redis.factory'],
        ],
        'serialization.phpserialize' => [
          'class' => 'Drupal\Component\Serialization\PhpSerialize',
        ],
      ],
    ];

Additional cache optimizations
===================

These settings allow to further optimize caching but are not be fully compatible
with the expected behavior of cache backends or have other tradeoffs.

Treat invalidateAll() the same as deleteAll() to avoid two different checks for
each bin.

    $settings['redis_invalidate_all_as_delete'] = TRUE;

Core may deprecate invalidateAll() in the future, this is essentially the same
as https://www.drupal.org/project/drupal/issues/3498947.

Additional configuration and features.
===============

Lock Backend
------------

See the provided example.services.yml file on how to override the lock services.

Queue Backend
------------------------------------

This module provides reliable and non-reliable queue implementations. Depending
on which is to be use you need to choose "queue.redis" or "queue.redis_reliable"
as a service name.

When you have configured basic information (host, library, ... - see Quick setup)
add this to your settings.php file:

    # Use for all queues unless otherwise specified for a specific queue.
    $settings['queue_default'] = 'queue.redis';

    # Or if you want to use reliable queue implementation.
    $settings['queue_default'] = 'queue.redis_reliable';

    # Use this to only use Redis for a specific queue (aggregator_feeds in this case).
    $settings['queue_service_aggregator_feeds'] = 'queue.redis';

    # Or if you want to use reliable queue implementation.
    $settings['queue_service_aggregator_feeds'] = 'queue.redis_reliable';

Use persistent connections
--------------------------

This mode needs the following setting:

    $settings['redis.connection']['persistent'] = TRUE;

Using a specific database
-------------------------

Per default, Redis ships the database "0". All default connections will be use
this one if nothing is specified.

Depending on you OS or OS distribution, you might have numerous database. To
use one in particular, just add to your settings.php file:

    $settings['redis.connection']['base']      = 12;

Connection to a password protected instance
-------------------------------------------

If you are using a password protected instance, specify the password this way:

    $settings['redis.connection']['password'] = "mypassword";

Depending on the backend, using a wrong auth will behave differently:

 - Predis will throw an exception and make Drupal fail during early boostrap.

 - PhpRedis will make Redis calls silent and creates some PHP warnings, thus
   Drupal will behave as if it was running with a null cache backend (no cache
   at all).

Prefixing site cache entries (avoiding sites name collision)
------------------------------------------------------------

If you need to differentiate multiple sites using the same Redis instance and
database, you will need to specify a prefix for your site cache entries.

Cache prefix configuration attempts to use a unified variable across contrib
backends that support this feature. This variable name is 'cache_prefix'.

This variable is polymorphic, the simplest version is to provide a raw string
that will be the default prefix for all cache bins:

    $settings['cache_prefix'] = 'mysite_';

Alternatively, to provide the same functionality, you can provide the variable
as an array:

    $settings['cache_prefix']['default'] = 'mysite_';

This allows you to provide different prefix depending on the bin name. Common
usage is that each key inside the 'cache_prefix' array is a bin name, the value
the associated prefix. If the value is FALSE, then no prefix is
used for this bin.

The 'default' meta bin name is provided to define the default prefix for non
specified bins. It behaves like the other names, which means that an explicit
FALSE will order the backend not to provide any prefix for any non specified
bin.

Here is a complex sample:

    // Default behavior for all bins, prefix is 'mysite_'.
    $settings['cache_prefix']['default'] = 'mysite_';

    // Set no prefix explicitly for 'cache' and 'cache_bootstrap' bins.
    $settings['cache_prefix']['cache'] = FALSE;
    $settings['cache_prefix']['cache_bootstrap'] = FALSE;

    // Set another prefix for 'cache_menu' bin.
    $settings['cache_prefix']['cache_menu'] = 'menumysite_';

Note that if you don't specify the default behavior, the Redis module will
attempt to use the HTTP_HOST variable in order to provide a multisite safe
default behavior. Notice that this is not failsafe, in such environment you
are strongly advised to set at least an explicit default prefix.

Note that this last notice is Redis only specific, because per default Redis
server will not namespace data, thus sharing an instance for multiple sites
will create conflicts. This is not true for every contributed backends.

Flush mode
----------

@todo: Update for Drupal 8

Redis allows to set a time-to-live at the key level, which frees us from
handling the garbage collection at clear() calls; Unfortunately Drupal never
explicitly clears single cached pages or blocks. If you didn't configure the
"cache_lifetime" core variable, its value is "0" which means that temporary
items never expire: in this specific case, we need to adopt a different
behavior than letting Redis handling the TTL by itself; This is why we have
three different implementations of the flush algorithm you can use:

 * 0: Never flush temporary: leave Redis handling the TTL; This mode is
   not compatible for the "page" and "block" bins but is the default for
   all others.

 * 1: Keep a copy of temporary items identifiers in a SET and flush them
   accordingly to spec (DatabaseCache default backend mimic behavior):
   this is the default for "page" and "block" bin if you don't change the
   configuration.

 * 2: Flush everything including permanent or valid items on clear() calls:
   this behavior mimics the pre-1.0 releases of this module. Use it only
   if you experience backward compatibility problems on a production
   environment - at the cost of potential performance issues; All other
   users should ignore this parameter.

You can configure a default flush mode which will override the sensible
provided defaults by setting the 'redis_flush_mode' variable.

  // For example this is the safer mode.
  $conf['redis_flush_mode'] = 1;

But you may also want to change the behavior for only a few bins.

  // This will put mode 0 on "bootstrap" bin.
  $conf['redis_flush_mode_cache_bootstrap'] = 0;

  // And mode 2 to "page" bin.
  $conf['redis_flush_mode_cache_page'] = 2;

Note that you must prefix your bins with "cache" as the Drupal 7 bin naming
convention requires it.

Keep in mind that defaults will provide the best balance between performance
and safety for most sites; Non-advanced users should ever change them.

Default lifetime for permanent items
------------------------------------

@todo: Update for Drupal 8

Redis when reaching its maximum memory limit will stop writing data in its
storage engine: this is a feature that avoid the Redis server crashing when
there is no memory left on the machine.

As a workaround, Redis can be configured as an LRU cache for both volatile or
permanent items, which means it can behave like Memcache; Problem is that if
you use Redis as a permanent storage for other business matters than this
module you cannot possibly configure it to drop permanent items, or you'll
lose data.

This workaround allows you to explicit set a very long or configured default
lifetime for CACHE_PERMANENT items (that would normally be permanent) which
will mark them as being volatile in Redis storage engine: this then allows you
to configure an LRU behavior for volatile keys without engaging the permanent
business stuff in a dangerous LRU mechanism; Cache items even if permanent will
be dropped when unused using this.

Per default the TTL for permanent items will set to safe-enough value which is
one year; No matter how Redis will be configured default configuration or lazy
admin will inherit from a safe module behavior with zero-conf.

For adventurous people, you can manage the TTL on a per bin basis and change
the default one:

    // Make CACHE_PERMANENT items being permanent once again
    // 0 is a special value usable for all bins to explicitly tell the
    // cache items will not be volatile in Redis.
    $conf['redis_perm_ttl'] = 0;

    // Make them being volatile with a default lifetime of 1 year.
    $conf['redis_perm_ttl'] = "1 year";

    // You can override on a per-bin basis;
    // For example make cached field values live only 3 months:
    $conf['redis_perm_ttl_cache_field'] = "3 months";

    // But you can also put a timestamp in there; In this case the
    // value must be a STRICTLY TYPED integer:
    $conf['redis_perm_ttl_cache_field'] = 2592000; // 30 days.

Time interval string will be parsed using DateInterval::createFromDateString
please refer to its documentation:

    http://www.php.net/manual/en/dateinterval.createfromdatestring.php

Last but not least please be aware that this setting affects the
CACHE_PERMANENT ONLY; All other use cases (CACHE_TEMPORARY or user set TTL
on single cache entries) will continue to behave as documented in Drupal core
cache backend documentation.

Testing
=======

The tests respect the following two environment variables to customize the redis
host and used interface.

  * REDIS_HOST
  * REDIS_INTERFACE

These can for example be configured through phpunit.xml

    <env name="REDIS_HOST" value="redis"/>
    <env name="REDIS_INTERFACE" value="Relay"/>
