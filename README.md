IcingaDB prototype
==================

This is where our current prototyping for `IcingaDB` takes place. It is not
meant for productional use at all. We might change ideas on a daily base, and
therefore push migration scripts that do not care about preserving data at all.

Dependencies
------------

* ipl
* icingaweb2-module-ipl
* Icinga Director >= 1.3.1
* MySQL (>= 5.5, higher is better)/MariaDB. PostgreSQL support will follow
* Redis server on your Icinga Web 2 system (>= 2.8)
* Icinga 2 instance(s) with (experimental) Redis feature enabled
* Stunnel exporting Redis (on Icinga node)

Installation
------------

Like any other module. Please see `etc` in case you want to keep sync daemons
running. Schema migration script currently fails as the code fails to correctly
extract procedures and the first schema file would require `SUPER` permissions.
Therefore please run it manually in the meantime. And do not run it on a shared
database server, as `mysql.sql` changes your global configuration.

Architecture
------------

We currently test with a distributed architecture to get aware of latency issues
large environments might face at an early stage. This module currently makes some
assumptions regarding your architecture:

* you can configure one or more independent environment
* for every environment, we connect to the deployment master configured in the
  Director
* Icinga nodes in different environments do not know each other
* Each Icinga node must have the experimental Redis feature enabled and works
  with a dedicated Redis server listening on localhost
* On every node the Redis service must be exported TLS-secured on port `5663`
* Icinga Web 2 sits on a dedicated node and must also have it's own Redis
  instance

Configuration
-------------

You need a dedicated database resource configured in Icinga Web 2 and referenced
in `ICINGAWEB_CONFIGDIR`/`modules/icingadb/config.ini` like this:

```ini
[db]
resource = "Icinga DB"
```

You might want to add a section for a dedicated local Redis instance. Next, a
configured Icinga Director is required. Add an environment in the `icingadb`
frontend, choose your Director DB. You might want to switch between multiple
Director databases (Infrastructure Dashboard, Kickstart) and add environments
for all of them.

### Stunnel configuration

As mentioned in the architecture section, we must export the Redis port allowing
encrypted traffic only. You might want to create a `/etc/stunnel/i2-redis.conf`
looking like this:

```ini
pid = /var/run/icinga2/i2-redis.pid

[local-redis]
accept = 0.0.0.0:5663
connect = 127.0.0.1:6379
client = no
setuid = nagios
setgid = nagios
cert = /etc/icinga2/pki/icinga-master1.lxd.crt
key = /etc/icinga2/pki/icinga-master1.lxd.key
CAfile = /etc/icinga2/pki/ca.crt
# checkHost = <server_host>
# verifyChain = yes
```

This isn't a secure configuration yet. Once we automated certificate signing
we'll enable the two bottom lines. There is no place to store environment-based
credentials yet. Grep for `insecure` in the code and replace it with a fixed
password for now. There you'll see that also the code used by the syncing daemons
has currently disabled certificate checks. The final version will refuse to work
without a valid certificate on both sides.
