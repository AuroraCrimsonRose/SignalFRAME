# SignalFRAME

**Build It. Stream It. Own It.**  
An open/modular radio station framework built by **CATALYSTS LABS**.

SignalFrame is a lightweight, scalable, and fully themeable self-hosted platform for managing multiple internet radio stations. It supports both hobbyist (personal) and commercial (multi-user, WHMCS-integrated) editions.

---

## Features

- Modular folder-per-station architecture
- Per-station theming and asset overrides
- Live text message updates per station (with safe editing)
- REST API with token support
- License control for edition-specific features
- Optional support for Icecast, Liquidsoap, and AzuraCast
- Future support: visualizer, desktop admin/audio client

---

## Getting Started

1. Clone this repo
2. Configure NGINX and PHP (Docker optional)
3. Place stations in `/stations/[station-name]/`
4. Set up your `license.json`
5. Open your browser at `/stations/example_station/`

---

## Deployment (Optional Docker)

Use the included `docker-compose.yml` to spin up NGINX, PHP, and optional streaming tools.

---

## License

This project is proprietary software with distinct personal and commercial licenses.  
See `LEGAL.md` for third-party license attribution.
