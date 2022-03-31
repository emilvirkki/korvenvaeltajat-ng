build: clean
	bundle exec jekyll build
clean:
	rm -rf _events
	bundle exec jekyll clean
serve:
	bundle exec jekyll serve --livereload --trace
install:
	echo Check if necessary tools are installed
	command -v bundle > /dev/null
	echo Install deps
	bundle install
