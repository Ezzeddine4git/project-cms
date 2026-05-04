# Camping Vibes

Laravel + Filament prototype for a French camping ecommerce storefront.

## Quick Start

```powershell
.\plug-and-play.ps1
```

This installs dependencies, prepares the configured database, publishes Filament assets, runs migrations, seeds demo content, links storage, runs tests, and starts the local server.

Useful options:

```powershell
.\plug-and-play.ps1 -SkipTests
.\plug-and-play.ps1 -NoServer
.\plug-and-play.ps1 -SkipNpm
```

## Database Helpers

Seed or reseed demo data:

```powershell
.\seed-database.ps1
```

Clean and rebuild the database with `migrate:fresh`, then seed demo data:

```powershell
.\clean-database.ps1
```

Clean without seeding:

```powershell
.\clean-database.ps1 -NoSeed
```

Run only `migrate:fresh`:

```powershell
.\fresh-database.ps1
```

Batch wrappers are also available:

```bat
seed-database.bat
clean-database.bat
fresh-database.bat
```

## Demo Accounts

Admin:

```text
abir@admin.com
admin
```

Additional demo admin:

```text
admin@camping-vibes.test
password
```

Demo customer:

```text
client@camping-vibes.test
password
```

## URLs

Public site:

```text
http://127.0.0.1:8000
```

Filament admin:

```text
http://127.0.0.1:8000/admin
```
