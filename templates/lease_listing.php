<!-- Main jumbotron for a primary marketing message or call to action -->
<div id="jumbotron" class="jumbotron jumbotron-fluid jumbo-min-height-properties">
  <!-- Lease search form for larger devices -->
  <div id="map"></div>
  <div class="lease-search-div">
    <form action="" class="lease-search-form">
      <input type="hidden" name="price_min" id="price-min" value="<?php echo $data['price_range']['vmin'];?>">
      <input type="hidden" name="price_max" id="price-max" value="<?php echo $data['price_range']['vmax'];?>">
      <input type="hidden" name="acre_min" id="acre-min" value="<?php echo $data['acre_range']['vmin'];?>">
      <input type="hidden" name="acre_max" id="acre-max" value="<?php echo $data['acre_range']['vmax'];?>">
      <h3 class="lease-search-title">LEASE SEARCH</h3>
      <div class="form-group">
          <label for="lease-search-price-min">Price [<?php echo '$' . number_format($data['price_range']['vmin'], 0) . ' - $' . number_format($data['price_range']['vmax'], 0);?>]</label>
          <div id="price-range"></div>
      </div>
      <div class="form-group">
          <label for="lease-search-acreage-slider">Acreage [<?php echo number_format($data['acre_range']['vmin'], 0) . ' - ' . number_format($data['acre_range']['vmax'], 0);?>]</label>
          <div id="acre-range"></div>
      </div>
      <div class="dropdown-row">
        <div class="dropdown-col form-group">
          <label for="field-state">State</label><br>
          <select name="state" id="field-state" class="lease-search-select-global custom-select">
            <option value="">Any</option>
          <?php
      		foreach($data['states'] as $k => $v){
            echo '<option value="'. $k .'"';
            if($k == $_GET['state']) echo ' selected';
            echo '>' . $k . "</option>\n";
          }
          ?>
          </select>
        </div>
        <div class="dropdown-col form-group">
          <label for="field-county">County</label><br>
          <select name="county" id="field-county" class="lease-search-select-global custom-select">
          <?php
      		foreach($data['counties'] as $v){
            echo '<option value="'. $v .'"';
            if($v == $_GET['county']) echo ' selected';
            echo '>' . $v . "</option>\n";
          }
          ?>
          </select>
          <img id="img-loading" class="no-visible" style="width: 25px;" src="<?php echo IMAGE_DIR . 'loading2.gif';?>">
        </div>
        <div class="dropdown-col form-group">
          <label for="lease-search-available">Available</label><br>
          <select name="availability" id="lease-search-available" class="lease-search-select-global custom-select">
            <option value="">Any</option>
            <option value="Available" <?php echo $_GET['availability'] == 'Available' ? 'selected': '';?>>Yes</option>
            <option value="Leased" <?php echo $_GET['availability'] == 'Leased' ? 'selected': '';?>>No</option>
          </select>
        </div>
      </div>
      <button type="submit" class="btn legacy-button lease-search-button">Search</button>
      <p><span class="lease-search-info">Legacy Wildlife Services is a division of Natural Resource Planning Services, Inc. (NRPS), a consulting forestry firm with over 40 years experience in the Southeastern United States. Specializing in wildlife consulting and hunting lease management.</span></p>
      <p>For more information, call <?php echo get_theme_mod('lgcy_phone_number');?></p>
    </form>
  </div>
  <!-- Lease search form for larger devices -->
</div>
<!-- /Jumbotron -->

<!-- Listings Section -->
<section id="listings-section">
  <div class="wrapper">
    <div id="listings-tabs-row" class="listings-tabs-row">
      <a class="listings-tab active" href="<?php echo home_url('/properties');?>">All Properties</a>
      <a class="listings-tab" href="<?php echo home_url('/properties?availability=Available');?>">Currently Available</a>
    </div>
    <div class="container" style="max-width: 100%;"><div class="row">
    <?php
    if(sizeof($data['properties'])){
      foreach($data['properties'] as $property){
    ?>
    <div class="featured-property col-xl-3 col-lg-6 col-md-12">
      <a href="<?php echo $property['link'];?>"><img src="<?php echo $property['image'];?>" alt="<?php echo $property['title'];?>"></a>
      <div class="featured-properties-content">
        <h4><?php echo $property['county'] . ", " . $property['state'];?></h4>
        <p>
        <?php if($property['size'] && $property['size_type']) { ?><?php echo number_format($property['size'],2) . ' ' . $property['size_type'];?><br> <?php } ?>
        <?php if($property['price'] && $property['availability'] == 'Available') { ?><b>$<?php echo number_format($property['price'],2);?></b><br><?php } else echo '<br>'; ?>
      </p>
        <a class="btn legacy-button subtitle-font-weight" href="<?php echo $property['link'];?>">
          VIEW PROPERTY
        </a>
      </div>
    </div>
    <?php
      }
    ?>
    </div></div>
    <div class="full-width navigation-container">
        <div class="navigation">
        <?php echo $data['pagination']; ?>
        </div>
    </div>
    <?php
    }else{
      echo '<h2>No properties found for your criteria. Please try again</h2>';
    }
    ?>
  </div>
</section>
<!-- /Listings Section -->

<!-- InfoWindow template -->
<script type="text/html" id="template">
  <div class="info-container">
    <h3><a href="%link%">%title%</a></h3>
    <a href="%link%"><img src="%image%"></a>
    <p>%county%, %state%</p>
  </div>
</script>
<!-- /InfoWindow template -->

<script>
var map;
function initMap() {
  map = new google.maps.Map(document.getElementById('map'), {
    center: {lat: <?php echo $data['center']['lat'];?>, lng: <?php echo $data['center']['lng'];?>},
    scrollwheel: false,
    zoom: 9
  });

<?php
  foreach($data['properties'] as $property){
    if($property['lat'] && $property['lng']){
?>
  vars = { title: '<?php echo $property['title'];?>', link : '<?php echo $property['link'];?>', image: '<?php echo $property['image'];?>', county: '<?php echo $property['county'];?>', state: '<?php echo $property['state'];?>' };
  content = theme('template', vars);
  set_marker(<?php echo $property['lat'];?>, <?php echo $property['lng'];?>, '<?php echo $property['title'];?>', content);
<?php
    }
  }
?>
}

function set_marker(lat, lng, title, content){
  var marker = new google.maps.Marker( { position: {'lat' : lat, 'lng' : lng}, 'map': map, 'title' : title } );
  var infowindow = new google.maps.InfoWindow( { 'content' : content } );

  marker.addListener('click', function() {
    infowindow.open(map, marker);
  });
}

//Theme function
// tpl_id : template id script
//  data  : object with variables for template
function theme(tpl_id, data){
  html = document.getElementById(tpl_id).innerHTML;

  for (var key in data) {
    pattern = new RegExp('%'+ key +'%',"g");
    html = html.replace(pattern, data[key]);
  }

  return html;
}

</script>
<script>
/**
 * Number.prototype.format(n, x, s, c)
 *
 * @param integer n: length of decimal
 * @param integer x: length of whole part
 * @param mixed   s: sections delimiter
 * @param mixed   c: decimal delimiter
 */
Number.prototype.format = function(n, x, s, c) {
    var re = '\\d(?=(\\d{' + (x || 3) + '})+' + (n > 0 ? '\\D' : '$') + ')',
        num = this.toFixed(Math.max(0, ~~n));

    return (c ? num.replace('.', c) : num).replace(new RegExp(re, 'g'), '$&' + (s || ','));
};

(function($){
  $(document).ready(function(){
    $( "#price-range" ).slider({
      range: true,
      step: 100,
      min: <?php echo $data['price_range']['min'];?>,
      max: <?php echo $data['price_range']['max'];?>,
      values: [ <?php echo $data['price_range']['vmin'] . ', ' . $data['price_range']['vmax'];?> ],
      slide: function( event, ui ) {
        $('#price-min').val( ui.values[0] );
        $('#price-max').val( ui.values[1] );
        $('label[for="lease-search-price-min"]').html( 'Price [$'+ ui.values[0].format(0, 3, ',', '.') + ' - $' + ui.values[1].format(0, 3, ',', '.') +']' );
      }
    });

    $( "#acre-range" ).slider({
      range: true,
      step: 10,
      min: <?php echo $data['acre_range']['min'];?>,
      max: <?php echo $data['acre_range']['max'];?>,
      values: [ <?php echo $data['acre_range']['vmin'] . ', ' . $data['acre_range']['vmax'];?> ],
      slide: function( event, ui ) {
        $('#acre-min').val( ui.values[0] );
        $('#acre-max').val( ui.values[1] );
        $('label[for="lease-search-acreage-slider"]').html( 'Acreage ['+ ui.values[0].format(0, 3, ',', '.') + ' - ' + ui.values[1].format(0, 3, ',', '.') +']' );
      }
    });
  });
})(jQuery);
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo theme_mod('lgcy_map_key');?>&callback=initMap" async defer></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js"></script>
