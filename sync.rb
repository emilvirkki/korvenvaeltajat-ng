require 'contentful'
require 'yaml'

EVENTS_DIR = '_events'

def write_entry(path, front_matter, content)
    puts "**********"
    puts "Writing #{path}:"
    puts front_matter.to_yaml
    puts content
    File.open(path, "w") do |file|
        file.write(front_matter.to_yaml)
        file.write("---\n")
        file.write(content)
    end
    puts "**********"
end

def assets_to_hash(assets)
    return if assets.nil?
    assets.map do |asset|
        {
            "url" => asset.url,
            "content_type" => asset.fields[:file].content_type,
            "title" => asset.fields[:title],
        }
    end
end

client = Contentful::Client.new(
  space: ENV['CONTENTFUL_SPACE'],
  access_token: ENV['CONTENTFUL_TOKEN']
)

Dir.mkdir(EVENTS_DIR) unless File.exists?(EVENTS_DIR)
client.entries(content_type: 'event').each do |event|
    puts event.fields
    front_matter = {
        "title" => event.fields[:title],
        "slug" => event.fields[:slug],
        "datetime_start" => event.fields[:datetime_start],
        "datetime_end" => event.fields[:datetime_end],
        "registration_link" => event.fields[:registration_link],
        "attachments" => assets_to_hash(event.fields[:attachments]),
        "kuksa_id" => event.fields[:kuksa_id],
    }
    content = event.fields[:content]
    write_entry("#{EVENTS_DIR}/#{event.fields[:slug]}.md", front_matter, content)
end
