# symfony

## Installation

Créez le fichier docker-compose.yml

```basj
$ docker-compose up -d --build
$ docker-compose exec web symfony new . --version="7.2.x" --webapp --no-interaction
```

Créez le dossier app, qui contiendra l'application