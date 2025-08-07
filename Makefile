.PHONY: *

up:
	docker compose up -d

db:
	docker exec -it news-importer-php composer db-migrate

test:
	docker exec -it news-importer-php composer test
