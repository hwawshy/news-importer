# News Importer

## Setting up the project

- clone the repository
- start the containers `make up`
- migrate the database `make db`
- execute the tests `make test`

## Using the the application

- upload the provided csv file to start an import `curl -i -F 'file=@./app/tests/Integration/Service/Resources/test_cases.csv' http://localhost/upload`
- get the import id from the response
- track the status of the import at `http://localhost/status/{importId}`
- list imports by status (running, success, failure) at `http://localhost/list/{status}`
- download excel files with import errors at `http://localhost/errors/{importId}`