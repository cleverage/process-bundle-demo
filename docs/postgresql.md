Running the demo on PostgreSQL
==============================

The demo runs on MySQL by default. It can run on PostgreSQL instead: the bundle migrations
(`cleverage/ui-process-bundle`), the demo migration (`author` / `book` tables) and the fixtures support both databases.

The database is chosen **when installing the project**, before the first `make start`.

## Configuration

1. In `.docker/compose.yaml`:
   - comment out the `mysql` service and uncomment the `postgres` service (and the `x-base-postgres` block at the top
     of the file);
   - in the `depends_on` of the `php-fpm` service, comment out `mysql` and uncomment `postgres`.
2. In `.env.local`, point Doctrine to PostgreSQL (the URL is also in `.env`, commented out):

   ```dotenv
   DATABASE_URL="postgresql://app:app@postgres:5432/app?serverVersion=16&charset=utf8"
   ```

3. Install and start the demo as usual:

   ```bash
   make start
   ```

   It runs the migrations and loads the fixtures on the PostgreSQL database. The UI is at
   http://process-bundle-demo.localhost/process (login `admin@clever-age.com` / `admin@clever-age.com`).

Both databases use the same `process_bundle_demo_data` volume.

## Variables

| Variable           | Default | Description                                     |
|--------------------|---------|-------------------------------------------------|
| `POSTGRES_VERSION` | `16`    | Tag of the `postgres:<version>` image.          |
| `POSTGRES_PORT`    | `5432`  | Port published on the host.                     |

If you change `POSTGRES_VERSION`, also change `serverVersion` in `DATABASE_URL`.

Connect to the database: `docker compose -f .docker/compose.yaml exec postgres psql -U app app`.
