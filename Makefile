build: clean sync
	bundle exec jekyll build
sync:
	test -f .env && source .env
	ruby sync.rb
clean:
	rm -r _events
	rm -r _articles
	rm _data/snippets.yml
	bundle exec jekyll clean
serve:
	bundle exec jekyll serve --livereload --trace
install:
	echo Check if necessary tools are installed
	command -v bundle > /dev/null
	echo Install deps
	bundle install
