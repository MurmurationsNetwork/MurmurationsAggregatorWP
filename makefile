.PHONY: all

all: clean build-map build-react

clean:
	rm -rf admin/assets

build-map:
	cd admin/map && npm i && npm run build

build-react:
	cd admin/react && npm i && npm run build

prepare:
	rm -rf .git .idea .vscode .gitignore admin/map admin/react
