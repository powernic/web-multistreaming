# RTSP Multi-Streamer for WEB (JPG & OGG)

The goal of this project is to create a Docker-deployed service
that will allow you to easily broadcast live video from IP cameras to web pages.
This stream rebroadcaster is designed to be used by many sources/users.
The service was developed as part of another [free parking monitoring project]( https://github.com/powernic/parking-lot-occupancy)

## Usage

```yaml
version: '3.7' 
services:
  multistreaming:
    image: powernic/web-multistreaming
    ports:
      - 80:80 
    restart: always
    volumes:
      - ./example-config.json:/app/config.json
    environment:
      - TYPE=file
      - CONFIG=/app/config.json
      - MESSENGER_TRANSPORT_DSN=redis://redis:6379/messages
  redis:
    image: redis:latest
    restart: always
    container_name: redis

```
[![Try in PWD](https://github.com/play-with-docker/stacks/raw/cff22438cb4195ace27f9b15784bbb497047afa7/assets/images/button.png)](https://labs.play-with-docker.com/?stack=https://raw.githubusercontent.com/powernic/web-multistreaming/master/docs/stack.yml)


## Environment variables

### TYPE
Available values: 
* `file` - load stream list from file
* `rest` - load stream list from REST API
* `remote_file` - load stream list from remote file
### CONFIG
Path to config file. Required if `TYPE` is `file`. 
### API_USERNAME
Username for REST API. Required if `TYPE` is `rest`.
### API_PASSWORD
Password for REST API. Required if `TYPE` is `rest`.
### FFSERVER_PORT
Port for FFServer. Default: `80`.
### MESSENGER_TRANSPORT_DSN
DSN for messenger transport. This is required for getting commands for updating config.

### 3. Create a configuration file

Create a file named `config.json` in the root of the project.

The file should contain a JSON object with the following structure:

```json
[
  {
    "id": "stream1",
    "url": "rtsp://..."
  }
] 
```

Here is an example of the JSON configuration file:

```json
[
  {
    "id": "bunny",
    "url": "rtsp://wowzaec2demo.streamlock.net/vod/mp4:BigBuckBunny_115k.mp4"
  },
  {
    "id": "bunny-test",
    "url": "rtsp://wowzaec2demo.streamlock.net/vod/mp4:BigBuckBunny_115k.mp4"
  }
]
```

Where:

* `id` - unique identifier of the stream
* `url` - RTSP stream URL

After starting the service, become available to you:

* Snapshot - `http://127.0.0.1/{id}-still.jpg`
* Video stream - `http://127.0.0.1/{id}-live.ogg`
* Status Page `http://127.0.0.1/status.html`

## Future work

Goals for future improvements to this project include:

* Adding more config streams sources (RestAPI with Client Credentials Grant, Authorization Code Grant, etc.)
* Make it optional to use a messenger transport to receive an event to update the configuration

Pull requests are welcome!

## Where to file issues:
Issues can be filed on https://github.com/powernic/web-multistreaming/issues
