# Roundcube JMAP
⚠️  This version is still in its early stages. This API exposes user data. It is not recommended to expose this API to untrusted networks for now. Please consider contacting us before using this in production.

The JMAP plugin for Roundcube provides [JMAP](https://jmap.io/) support for Roundcube by exposing a RESTful API Endpoint which speaks the JMAP Protocol.

The following data types are currently supported by the JMAP Plugin for Roundcube:

* Signatures over the [JMAP for Mail](https://www.rfc-editor.org/rfc/rfc8621) protocol
* Contacts over the JMAP for Contacts based on JSContact protocol (see https://www.audriga.eu/jmap/jscontact/ )

The plugin could be used as a modern replacement for the CardDAV endpoint (also CalDAV in a future version).

## Installation
1. Run `make` to initialize the project for the default PHP version (8.1). Use other build targets (e.g. `make php56_mode` or `make php70_mode`) instead, in case you are using a different version.
1. (optional) there are build targets that enable logging to graylog instead of a file, e.g. run `make graylog56_mode`
1. Run `make zip` to create a zipped package under `build/`
1. Extract the resulting package into the `plugins` folder of your Roundcube (Make sure the folder is named `jmap`).
1. 🎉 Partytime! Help fix [some issues](https://github.com/audriga/jmap-roundcube/issues) and [send us some pull requests](https://github.com/audriga/jmap-roundcube/pulls) 👍

## Usage
Set up your favorite client to talk to Roundcube's JMAP API.

## Development
### Installation
1. Run step 1) from above.
1. Run `make update` to update depdendencies and make devtools available

### Tests
To run all tests run `make fulltest`. This requires [Podman](https://podman.io/)
(for Static Anaylsis) and [Ansible](https://www.ansible.com/) (for Integration
Tests).

You can also run them separately:

* **Static Analysis** via `make lint`
* **Unit Tests** via `make unit_test`

#### Tested against

PHP: 5.6, 7.0, 8.1 and 8.2

Roundcube: 1.2, 1.3, 1.4 and 1.6

### Debug
For debugging purposes it makes sense to throw some cURL calls at the API. For example, this is how you tell the JMAP API to return all Contacts:

```
curl <roundcube-address>plugins/jmap/jmap.php -u <username>:<password> -d '{"using":["https://www.audriga.eu/jmap/jscontact/"],"methodCalls":[["Card/get", {"accountId":"<username>"}, "0"]]}'
```