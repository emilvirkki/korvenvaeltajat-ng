build: clean sync
	bundle exec jekyll build
sync:
	test -f .env && source .env || echo "No env file found"
	ruby sync.rb
clean:
	rm -rf _events
	rm -rf _articles
	rm -f _data/snippets.yml
	bundle exec jekyll clean
serve:
	bundle exec jekyll serve --livereload --trace
install:
	echo Check if necessary tools are installed
	command -v bundle > /dev/null
	echo Install deps
	bundle install
