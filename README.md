# Bulletin Hub 

A laravel back end project to get news from different APIs and sources, with JWT auth.

## Requirements

- **composer**
- **NewsData API token**
- **Currents API token**

## Installation

1. **Go to the root directory of the project:**
   ```bash
   cd /your/path/to/bulletin-hub
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

## Usage

1. **Start the docker container:**
   ```bash
   ./vendor/bin/sail up
   ```
   By default, the server will run at `http://localhost:80`.

2. **Access the form in your browser:**
   ```
   http://localhost:80/
   ```

## Project Structure

This project follows the default [Laravel v11 folder structure](https://laravel.com/docs/11.x/structure)


## License

This project is licensed under the GPL v3 license.

## Author

**Heitor Stael**  
Email: [heitorstael@live.com](mailto:heitorstael@live.com)
