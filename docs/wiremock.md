## Mocking APIs with WireMock

The `wiremock` service of the Docker stack (`.docker/compose.yaml`) runs [WireMock](https://wiremock.org/), which mocks
the HTTP APIs called by the processes: no network access is needed, the responses are known in advance, and the error
cases (status codes, transport errors...) can be reproduced at will.

### Setup

- **Stubs**: one JSON file per stub in `.docker/wiremock/mappings/` (request to match, response to return), response
  bodies in `.docker/wiremock/__files/`. They are loaded when the container starts: run
  `docker compose -f .docker/compose.yaml restart wiremock` after a change (or `POST /__admin/mappings/reset`).
- **Response templating** is enabled for every stub (`--global-response-templating`): a response can use the request
  (`{{request.pathSegments.[2]}}`, `{{jsonPath request.body '$.title'}}`...), see
  [response templating](https://wiremock.org/docs/response-templating/).
- **REST clients** (`config/services.yaml`): `wiremock` on the mocked API (`%env(WIREMOCK_URL)%/api`), and
  `wiremock_admin` on the [admin API](https://wiremock.org/docs/standalone/admin-api-reference/)
  (`%env(WIREMOCK_URL)%/__admin`). `WIREMOCK_URL` is defined in `.env` (`http://wiremock:8080`).
- **Admin UI / API** from the host: http://localhost:8089/__admin (the host port can be changed with `WIREMOCK_PORT`
  in `.docker/.env`), e.g. `curl http://localhost:8089/__admin/requests` lists the received requests,
  `__admin/requests/unmatched` the ones that matched no stub (useful to write a new stub).

### Use cases

| Process                         | Stub(s) (`.docker/wiremock/mappings/`)                 | Shows                                                                                         |
|---------------------------------|--------------------------------------------------------|-----------------------------------------------------------------------------------------------|
| `demo.wiremock.get_json`        | `books-list.json`                                      | Static JSON response read from a body file (`bodyFileName`), decoded and iterated             |
| `demo.wiremock.path_templating` | `books-get.json`                                       | Matching on a path pattern (`urlPathPattern`), response built from the requested id           |
| `demo.wiremock.post_json`       | `books-create.json`, `books-create-invalid.json`       | Matching on the JSON body (`matchesJsonPath`), stub priorities, `422` sent to `error_outputs` |
| `demo.wiremock.error_codes`     | `books-not-found.json`, `books-server-error.json`      | `404` / `500` responses with `error_strategy: skip` and `error_outputs`                       |
| `demo.wiremock.authentication`  | `authors-authorized.json`, `authors-unauthorized.json` | Matching on a header (bearer token), with a lower priority fallback stub (`401`)              |
| `demo.wiremock.fault`           | `fault-connection-reset.json`                          | Fault injection (`CONNECTION_RESET_BY_PEER`): transport error, the process fails              |
| `demo.wiremock.verify_requests` | `books-list.json`, `books-get.json`                    | Admin API: reset the request journal, call the API, count the received requests               |

Run them with `bin/console cleverage:process:execute <process>` (`make bash` first).

### Adding a stub

1. Call the API once and look at the unmatched request: `curl http://localhost:8089/__admin/requests/unmatched`.
2. Add a mapping file, e.g. `.docker/wiremock/mappings/books-search.json`:

```json
{
  "request": { "method": "GET", "urlPath": "/api/books", "queryParameters": { "q": { "matches": ".+" } } },
  "response": { "status": 200, "headers": { "Content-Type": "application/json" }, "jsonBody": [] }
}
```

3. Restart the container, or create the stub at runtime: `curl -X POST http://localhost:8089/__admin/mappings -d @books-search.json`
   (runtime stubs are lost when the container restarts).

When several stubs match a request, the one with the lowest `priority` wins (`5` by default): a generic stub with a
high priority value can serve as a fallback (see `authors-unauthorized.json`).
