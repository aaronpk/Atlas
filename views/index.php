<div class="page">

  <h2><i class="fa fa-clock-o"></i> Timezone</h2>

    <h3>Retrieving the timezone at a lat/lng</h3>

    <ul>
      <li><a href="/api/timezone?latitude=45.5118&amp;longitude=-122.6433">/api/timezone?latitude=45.5118&amp;longitude=-122.6433</a></li>
      <li><a href="/api/timezone?airport=PDX">/api/timezone?airport=PDX</a></li>
    </ul>

  <h2><i class="fa fa-globe"></i> Geocoder</h2>

    <h3>Retrieving the lat/lng for a named location</h3>

    <ul>
      <li><a href="/api/geocode?input=309+SW+6th+Ave,+Portland,+OR">/api/geocode?input=309+SW+6th+Ave,+Portland,+OR</a></li>
    </ul>

    <h3>Retrieving a named location from a lat/lng</h3>

    <ul>
      <li><a href="/api/geocode?latitude=45.5118&amp;longitude=-122.6433">/api/geocode?latitude=45.5118&amp;longitude=-122.6433</a></li>
      <li><a href="/api/geocode?latitude=45.5118&amp;longitude=-122.6433&date=2016-07-12T09:00:00Z">/api/geocode?latitude=45.5118&amp;longitude=-122.6433&date=2016-07-012T09:00:00Z</a> and return the local time of the given timestamp</li>
    </ul>

  <h2><i class="fa fa-sun-o"></i> Weather</h2>

    <h3>Retrieving the current weather for a lat/lng</h3>

    <ul>
      <li><a href="/api/weather?latitude=45.5118&amp;longitude=-122.6433&amp;apikey=XXX">/api/weather?latitude=45.5118&amp;longitude=-122.6433&amp;apikey=XXX</a></li>
    </ul>

    <p>You'll need to pass an OpenWeatherMap.org API key in the request. Icon names reference the <a href="https://erikflowers.github.io/weather-icons/">weather-icons</a> icon font.</p>

  <h2><i class="fa fa-map"></i> Static Maps</h2>

    <p><a href="/map/img?marker[]=lat:45.5165;lng:-122.6764;icon:small-blue-cutout&amp;basemap=gray&amp;width=600&amp;height=240&amp;zoom=14">/map/img?marker[]=lat:45.5165;lng:-122.6764;icon:small-blue-cutout&amp;basemap=gray&amp;width=600&amp;height=240&amp;zoom=14</a></p>
    <img src="/assets/sample-map.png" width="600"/>

    <p>See <a href="https://github.com/aaronpk/Static-Maps-API-PHP/">Static-Maps-API</a> for full API docs</p>

</div>
