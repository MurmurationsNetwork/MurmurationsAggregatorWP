.PHONY: all

all: clean build-map build-react

clean:
	rm -r admin/assets/*

build-map:
	cd admin/map && npm run build

build-react:
	cd admin/react && npm run build

prepare:
	rm -rf .git .idea .gitignore admin/map admin/react
