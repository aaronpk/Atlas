<header class="hero">
  <div class="wrap">
    <h1>Atlas</h1>
    <p class="lede">A set of APIs for looking up information about locations: timezones, geocoding, current weather, and static map images.</p>
  </div>
</header>

<main class="wrap">

  <section class="card" id="timezone">
    <h2><span class="icon"><i class="fa fa-clock-o"></i></span> Timezone</h2>
    <p>The timezone at a latitude/longitude, or at an airport by its IATA code, with the current offset and local time.</p>
    <ul class="endpoints">
      <li><a href="/api/timezone?latitude=45.5118&amp;longitude=-122.6433"><span class="method">GET</span><code>/api/timezone?latitude=45.5118&amp;longitude=-122.6433</code></a></li>
      <li><a href="/api/timezone?airport=PDX"><span class="method">GET</span><code>/api/timezone?airport=PDX</code></a></li>
    </ul>
  </section>

  <section class="card" id="geocoder">
    <h2><span class="icon"><i class="fa fa-globe"></i></span> Geocoder</h2>
    <h3>The latitude/longitude of a named location</h3>
    <ul class="endpoints">
      <li><a href="/api/geocode?input=309+SW+6th+Ave,+Portland,+OR"><span class="method">GET</span><code>/api/geocode?input=309+SW+6th+Ave,+Portland,+OR</code></a></li>
    </ul>
    <h3>The named location at a latitude/longitude</h3>
    <ul class="endpoints">
      <li><a href="/api/geocode?latitude=45.5118&amp;longitude=-122.6433"><span class="method">GET</span><code>/api/geocode?latitude=45.5118&amp;longitude=-122.6433</code></a></li>
      <li><a href="/api/geocode?latitude=45.5118&amp;longitude=-122.6433&amp;date=2016-07-12T09:00:00Z"><span class="method">GET</span><code>/api/geocode?latitude=45.5118&amp;longitude=-122.6433&amp;date=2016-07-12T09:00:00Z</code></a>
        <span class="note">Also returns the local time of the given timestamp.</span></li>
    </ul>
  </section>

  <section class="card" id="weather">
    <h2><span class="icon"><i class="fa fa-sun-o"></i></span> Weather</h2>
    <p>The current weather at a latitude/longitude, with today's sunrise and sunset.</p>
    <ul class="endpoints">
      <li><a href="/api/weather?latitude=45.5118&amp;longitude=-122.6433&amp;apikey=XXX"><span class="method">GET</span><code>/api/weather?latitude=45.5118&amp;longitude=-122.6433&amp;apikey=XXX</code></a></li>
    </ul>
    <p class="note">Pass your own <a href="https://openweathermap.org/api">OpenWeatherMap</a> API key as <code>apikey</code>. Icon names refer to the <a href="https://erikflowers.github.io/weather-icons/">weather-icons</a> icon font.</p>
  </section>

  <section class="card" id="static-maps">
    <h2><span class="icon"><i class="fa fa-map"></i></span> Static Maps</h2>
    <p>Map images with markers and paths, from a choice of basemaps.</p>
    <ul class="endpoints">
      <li><a href="/map/img?marker[]=lat:45.5165;lng:-122.6764;icon:small-blue-cutout&amp;basemap=gray&amp;width=600&amp;height=240&amp;zoom=14"><span class="method">GET</span><code>/map/img?marker[]=lat:45.5165;lng:-122.6764;icon:small-blue-cutout&amp;basemap=gray&amp;width=600&amp;height=240&amp;zoom=14</code></a></li>
    </ul>
    <figure class="example">
      <img src="/assets/sample-map.png" width="600" height="240" alt="A gray street map of downtown Portland with a blue marker">
    </figure>
    <p><a class="button" href="/static-maps">Static Maps API docs <i class="fa fa-arrow-right"></i></a></p>
  </section>

</main>
