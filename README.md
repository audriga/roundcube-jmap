# Roundcube JMAP
The JMAP plugin for Roundcube provides [JMAP](https://jmap.io/) support for Roundcube by exposing a RESTful API Endpoint which speaks the JMAP Protocol.

Please note that this version is still in its early stages.

The following data types are currently supported by the JMAP Plugin for Roundcube:

* Signatures over the [JMAP for Mail](https://www.rfc-editor.org/rfc/rfc8621) protocol
* Contacts over the JMAP for Contacts based on JSContact protocol (see https://www.audriga.eu/jmap/jscontact/ )

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

### Debug
For debugging purposes it makes sense to throw some cURL calls at the API. For example, this is how you tell the JMAP API to return all Contacts:

```
curl <roundcube-address>plugins/jmap/jmap.php -u <username>:<password> -d '{"using":["https://www.audriga.eu/jmap/jscontact/"],"methodCalls":[["Card/get", {"accountId":"<username>"}, "0"]]}'
```
