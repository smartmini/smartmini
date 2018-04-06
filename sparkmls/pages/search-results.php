<?php

class flexmlsConnectPageSearchResults extends flexmlsConnectPageCore {

  protected $search_criteria;
  protected $field_value_count;
  protected $search_data;
  protected $standard_fields;
  protected $total_pages;
  protected $current_page;
  protected $total_rows;
  protected $api;
  protected $page_size;
  protected $type;
  public $title;

  function __construct( $api, $type = 'fmc_tag' ){
    parent::__construct($api);
    $this->type = $type;
  }

  public function pre_tasks($tag) {  
    global $fmc_special_page_caught;
    global $fmc_api;
    global $fmc_plugin_url;

    //MlsStatus Eq 'Active' And PropertyType Eq 'A' And ListPrice Ge 1000.0 And BedsTotal Ge 4 And BathsTotal Ge 2.0
    

    $search_arr = array();
    if(!empty($_GET) && !empty($_GET['alaskaSearch']))
     {
      if(!empty($_GET['baths']))
      {
        $search_arr['MaxBaths'] = $_GET['baths'];
        $search_arr['MinBaths'] = $_GET['baths'];
      }

      if(!empty($_GET['beds']))
      {
        $search_arr['MaxBeds'] = $_GET['beds'];
        $search_arr['MinBeds'] = $_GET['beds'];
      } 

      if(!empty($_GET['min']))
      {
        $search_arr['MinPrice'] = $_GET['min'];
      }

      if(!empty($_GET['max']))
      {
        $search_arr['MaxPrice'] = $_GET['max'];
      }

      if(!empty($_GET['PropertyType']))
      {
        $search_arr['PropertyType'] = $_GET['PropertyType'];
      }else{
        //$search_arr['PropertyType'] = "A";
      }

      if(!empty($_GET['MinBaths']))
      {
        $search_arr['MinBaths'] = $_GET['MinBaths'];
      }

      if(!empty($_GET['MaxBaths']))
      {
        $search_arr['MaxBaths'] = $_GET['MaxBaths'];
      }

      if(!empty($_GET['MinYear']))
      {
        $search_arr['MinYear'] = $_GET['MinYear'];
      }

      if(!empty($_GET['MaxYear']))
      {
        $search_arr['MaxYear'] = $_GET['MaxYear'];
      }

      if(!empty($_GET['MinSqFt']))
      {
        $search_arr['MinSqFt'] = $_GET['MinSqFt'];
      }

      if(!empty($_GET['MaxSqFt']))
      {
        $search_arr['MaxSqFt'] = $_GET['MaxSqFt'];
      }

      if(!empty($_GET['MinPrice']))
      {
        $search_arr['MinPrice'] = $_GET['MinPrice'];
      }

      if(!empty($_GET['MaxPrice']))
      {
        $search_arr['MaxPrice'] = $_GET['MaxPrice'];
      }

      if(!empty($_GET['OrderBy']))
      {
        if (strpos($_GET['OrderBy'], '$') == false) {
            $search_arr['OrderBy'] = $_GET['OrderBy'];
        }        
      }

      if(!empty($_GET['StandardStatus']))
      {
        $search_arr['StandardStatus'] = $_GET['StandardStatus'];
      }else{
        //$search_arr['StandardStatus'] = "Active";
      }

      if(!empty($_GET['GarageSpaces']))
      {
        $search_arr['GarageSpaces'] = $_GET['GarageSpaces'];
      }



      if(!empty($_GET['SavedSearch']))
      {
        $search_arr['SavedSearch'] = $_GET['SavedSearch'];
      }
     // $search_arr['SavedSearch'] =  '20101018074152570937000000';
     // $search_arr['SavedSearch'] = "SavedSearch Eq '20170427164825001017000000' Or SavedSearch Eq '20170427165216949677000000' SavedSearch Eq Or '20170427165103666489000000')";
      //$search_arr['SavedSearch'] = "20100923173342309167000000";

      // mat : 20170427164825001017000000
      // kenai : 20170427165216949677000000  source="location" display="all" sort="recently_changed" status="Active"
      // anchorage : 20170427165103666489000000


      if(!empty($_GET['MLSAreaMinor']))
      {
        $search_arr['MLSAreaMinor'] = $_GET['MLSAreaMinor'];
      }   

      if(!empty($_GET['City']))
      {
        $search_arr['City'] = $_GET['City'];
      }   

      
    }

   // echo "<pre>"; print_r($search_arr); 

    list($params, $cleaned_raw_criteria, $context) = $this->parse_search_parameters_into_api_request($search_arr);   

    //echo "<pre>"; print_r($params); //die;
    $this->search_criteria = $cleaned_raw_criteria;

    //This unset was added to pull all information
    unset($params['_select']);
    //Set page size to cookie value
    $this->page_size= empty($_COOKIE['flexmlswordpressplugin']) ? 12 : intval($_COOKIE['flexmlswordpressplugin']) ;


    if ($this->page_size > 0 and $this->page_size <= 25){
      //Good, don't need to to anything
    }
    elseif ($this->page_size>25){
      $this->page_size=25;
    }
    else {
      $this->page_size=12;
    }

    $params['_limit'] = $this->page_size;
    if ($context == "listings") {
      $results = $this->api->GetMyListings($params);
      
    }
    elseif ($context == "office") {
      $results = $this->api->GetOfficeListings($params);
    }
    elseif ($context == "company") {
      $results = $this->api->GetCompanyListings($params);
    }
    else {
      $cache_time = (strpos($params['_filter'],'ListingCart')!==false) ? 0 : '10m';
      $results = $this->api->GetListings($params, 0);
    }
// echo $this->total_rows =  $this->api->last_count;
//    echo "<pre>"; print_r($results); die;

    $this->title = !empty($this->title) ? $this->title : "";
    $this->search_data = $results;
    $this->total_pages =  $this->api->total_pages;
    $this->current_page =  $this->api->current_page;
    $this->total_rows =  $this->api->last_count;
    $this->page_size =  $this->api->page_size;
    $fmc_special_page_caught['type'] = "search-results";
    $fmc_special_page_caught['page-title'] = "Property Search";
    $fmc_special_page_caught['post-title'] = "Property Search";
    $fmc_special_page_caught['page-url'] = flexmlsConnect::make_nice_tag_url('search') .'?'. $_SERVER['QUERY_STRING'];

  }

  function quick_search(){ //print_r($this->search_criteria);
    $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://"; 
    $current_url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $current_url = explode('?', $current_url);
    echo '<form class="search page-menu quick-search-dream" name="property_form" id="list_container_form" method="get" action="'.$current_url[0].'">';  

    if(!empty($_GET['OrderBy']))
    {
      echo '<input type="hidden" name="OrderBy" value="'.$_GET['OrderBy'].'">'; 
    }

    global $fmc_api;

    // pull StandardFields from the API to verify searchability prior to searching
    $city = $fmc_api->GetStandardField('City');   

//echo "<pre>"; print_r($area); die;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="?alaskaSearch=advance" style="color:#234c7a;">Advance Search</a>
    
    echo '<input type="hidden" name="alaskaSearch" value="quick"><div class="row-fluid"><h3 class="span2" style="width:100%;">Quick Search </h3>
    <div class="span2" style="position: relative;">';

                if(!empty($_GET['City']))
                {
                  echo '<label class="labelcity">Closest USPS Town</label>';
                  //echo '<input type="text" name="City" id="closest_City11" placeholder="Closest USPS Town"  value="'.$_GET['City'].'" list="closest_City"><datalist id="closest_City">';
                }else{
                  echo '<label class="labelcity"></label>';
                  //echo '<input type="text" name="City" id="closest_City11" placeholder="Closest USPS Town"  list="closest_City"><datalist id="closest_City">';
                }



                echo '<select id="ddlCars" multiple="multiple" name="City[]" onChange="togglelabel(\'labelcity\',\'Closest USPS Town\',this.value)">';          
          

                foreach ($city[0]['City']['FieldList'] as  $key=>$value) {
                  echo '<option value="'.$value['Value'].'">'.$value['Value'].'</option>';
                }
                echo '</select>';

                $status = array('Active'=>'Active','Pending'=>'Pending','Closed'=>'Closed');      
                  
                echo '</div>
                <div class="span2">';

                if(!empty($_GET['StandardStatus']))
                {
                   echo '<label class="labelstatus">Status</label>';
                }else if(!empty($this->search_criteria['StandardStatus']) && empty($_GET['alaskaSearch']))
                {
                  echo '<label class="labelstatus">Status</label>';
                }else
                {
                  echo '<label class="labelstatus"></label>';
                }
                

           echo '<select class="input-block-level quick" name="StandardStatus" id="StandardStatus" onChange="togglelabel(\'labelstatus\',\'Status\',this.value)">';
                  if(!empty($_GET['StandardStatus']))
                  {
                    echo ' <option value="0">Status</option>';
                  }else{
                    echo ' <option value="0" selected="selected">Status</option>';
                  }
                  foreach ($status as $key => $value) {
                    $selected = "";
                    if(!empty($_GET['StandardStatus']) && $_GET['StandardStatus'] == $value)
                    {
                      $selected = 'selected="selected"';
                    }else if(!empty($this->search_criteria['StandardStatus']) && $this->search_criteria['StandardStatus'] == $value && empty($_GET['alaskaSearch']))
                    {
                       $selected = 'selected="selected"';
                    }
                    echo '<option  value="'.$value.'" '.$selected.'>'.$key.'</option>';
                  }
                        
                 echo '</select>
                </div>  
                <div class="span2">';

                if(!empty($_GET['PropertyType']))
                {
                   echo '<label class="labelPropertyType">Property Type</label>';
                }else{
                  echo '<label class="labelPropertyType"></label>';
                }
                

           echo '<select class="input-block-level quick" name="PropertyType" id="dream_PropertyType" onChange="togglelabel(\'labelPropertyType\',\'Property Type\',this.value)">';
                  //$PropertyType = array('Residential'=>'A','Condominium'=>'B','Vacant Land'=>'C','Multi-Family'=>'D','Commercial'=>'E','Business Op'=>'F','Commercial Lease'=>'G','Residential Lease'=>'H','Mobile Homes'=>'I');   
                  $PropertyType = array('Residential'=>'A','Condominium'=>'B','Vacant Land'=>'C','Multi-Family'=>'D');   
                        
                        if(!empty($_GET['PropertyType']))
                        {
                          echo ' <option value="0">Property Type</option>';
                        }else{
                          echo ' <option value="0" selected="selected">Property Type</option>';
                        }
                        foreach ($PropertyType as $key => $value) {
                          $selected = "";
                          if(!empty($_GET['PropertyType']) && $_GET['PropertyType'] == $value)
                          {
                            $selected = 'selected="selected"';
                          }
                          echo '<option  value="'.$value.'" '.$selected.'>'.$key.'</option>';
                        }

                  echo '</select>
                </div>                
                <div class="span2">';

                if(!empty($_GET['beds']))
                {
                   echo '<label class="labelbeds">Beds</label>';
                }else{
                  echo '<label class="labelbeds"></label>';
                }
                

           echo '<select class="input-block-level quick" name="beds" id="beds" onChange="togglelabel(\'labelbeds\',\'Beds\',this.value)">';
                   $beds =array('1+'=>'1','2+'=>'2','3+'=>'3','4+'=>'4','5+'=>'5','6+'=>'6');   
                        
                        if(!empty($_GET['beds']) || !empty($_GET['MinBeds']))
                        {
                          echo ' <option value="0">Beds</option>';
                        }else{
                          echo ' <option value="0" selected="selected">Beds</option>';
                        }
                        foreach ($beds as $key => $value) {
                          $selected = "";
                          if(!empty($_GET['beds']) && $_GET['beds'] == $value || !empty($_GET['MinBeds']) && $_GET['MinBeds'] == $value)
                          {
                            $selected = 'selected="selected"';
                          }
                          echo '<option  value="'.$value.'" '.$selected.'>'.$key.'</option>';
                        }
                          
                  echo '</select>
                </div><!--span2-->

                <div class="span2">';

                if(!empty($_GET['baths']))
                {
                   echo '<label class="labelbaths">Baths</label>';
                }else{
                  echo '<label class="labelbaths"></label>';
                }
                

           echo '<select class="input-block-level quick" name="baths" id="baths" onChange="togglelabel(\'labelbaths\',\'Baths\',this.value)">';
                    $baths = array('1+'=>'1','1.5+'=>'1.5+','2+'=>'2','2.5+'=>'2.5','3+'=>'3','4+'=>'4','5+'=>'5','6+'=>'6');   
                        
                        if(!empty($_GET['baths']) || !empty($_GET['MinBaths']))
                        {
                          echo ' <option value="0">Baths</option>';
                        }else{
                          echo ' <option value="0" selected="selected">Baths</option>';
                        }
                        foreach ($beds as $key => $value) {
                          $selected = "";
                          if(!empty($_GET['baths']) && $_GET['baths'] == $value || !empty($_GET['MinBaths']) && $_GET['MinBaths'] == $value)
                          {
                            $selected = 'selected="selected"';
                          }
                          echo '<option  value="'.$value.'" '.$selected.'>'.$key.'</option>';
                        }

                        
                           
                    echo '</select>
                </div><!--span2-->

                <div class="span2">';

                if(!empty($_GET['min']) || !empty($_GET['MinPrice']))
                {
                   echo '<label class="labelminprice">Min Price</label>';
                }else{
                  echo '<label class="labelminprice"></label>';
                }
                

           echo '<select class="input-block-level quick" name="min" id="min2" onChange="togglelabel(\'labelminprice\',\'Min Price\',this.value)">';
                    $min = array('25,000'=>'25000','50,000'=>'50000','75,000'=>'75000','100,000'=>'100000','125,000'=>'125000','150,000'=>'150000','175,000'=>'175000','200,000'=>'200000','250,000'=>'250000','300,000'=>'300000','350,000'=>'350000','400,000'=>'400000','450,000'=>'450000','500,000'=>'500000','550,000'=>'550000','600,000'=>'600000','650,000'=>'650000','700,000'=>'700000','750,000'=>'750000','800,000'=>'800000','850,000'=>'850000','900,000'=>'900000','950,000'=>'950000','1,000,000'=>'1000000','1,050,000'=>'1050000','1,100,000'=>'1,100,000','1150000'=>'1,150,000','1,200,000'=>'1200000','1,250,000'=>'1250000','1,300,000'=>'1300000','1,350,000'=>'1350000','1,400,000'=>'1400000','1,600,000'=>'1600000','1,650,000'=>'1650000','1,700,000'=>'1700000','1,750,000'=>'1750000','1,800,000'=>'1800000','1,850,000'=>'1850000','1,900,000'=>'1900000','1,950,000'=>'1950000','2,000,000'=>'2000000','5,000,000'=>'5000000','10,000,000'=>'10000000');
                         if(!empty($_GET['min']) || !empty($_GET['MinPrice']))
                        {
                          echo ' <option value="0">Min Price</option>';
                        }else{
                          echo ' <option value="0" selected="selected">Min Price</option>';
                        }
                        foreach ($min as $key => $value) {
                          $selected = "";
                          if(!empty($_GET['min']) && $_GET['min'] == $value || !empty($_GET['MinPrice']) && $_GET['MinPrice'] == $value)
                          {
                            $selected = 'selected="selected"';
                          }
                          echo '<option  value="'.$value.'" '.$selected.'>$'.$key.'</option>';
                        }

                        echo '</select>
                </div><!--span2-->
                <div class="span2 relative">';

                if(!empty($_GET['max'])  || !empty($_GET['MaxPrice']))
                {
                   echo '<label class="labelmaxprice">Max Price</label>';
                }else{
                  echo '<label class="labelmaxprice"></label>';
                }
                

           echo '<span class="dash"></span>
                  <select class="input-block-level quick" name="max" id="max2" onChange="togglelabel(\'labelmaxprice\',\'Max Price\',this.value)">';
                      $max = array('50,000'=>'50000','75,000'=>'75000','100,000'=>'100000','125,000'=>'125000','150,000'=>'150000','175,000'=>'175000','200,000'=>'200000','250,000'=>'250000','300,000'=>'300000','350,000'=>'350000','400,000'=>'400000','450,000'=>'450000','500,000'=>'500000','550,000'=>'550000','600,000'=>'600000','650,000'=>'650000','700,000'=>'700000','750,000'=>'750000','800,000'=>'800000','850,000'=>'850000','900,000'=>'900000','950,000'=>'950000','1,000,000'=>'1000000','1,050,000'=>'1050000','1,100,000'=>'1,100,000','1150000'=>'1,150,000','1,200,000'=>'1200000','1,250,000'=>'1250000','1,300,000'=>'1300000','1,350,000'=>'1350000','1,400,000'=>'1400000','1,600,000'=>'1600000','1,650,000'=>'1650000','1,700,000'=>'1700000','1,750,000'=>'1750000','1,800,000'=>'1800000','1,850,000'=>'1850000','1,900,000'=>'1900000','1,950,000'=>'1950000','2,000,000'=>'2000000','5,000,000'=>'5000000','10,000,000'=>'10000000');
                         if(!empty($_GET['max'])  || !empty($_GET['MaxPrice']))
                        {
                          echo ' <option value="0">Max Price</option>';
                        }else{
                          echo ' <option value="0" selected="selected">Max Price</option>';
                        }
                        foreach ($max as $key => $value) {
                          $selected = "";
                          if(!empty($_GET['max']) && $_GET['max'] == $value || !empty($_GET['MaxPrice']) && $_GET['MaxPrice'] == $value)
                          {
                            $selected = 'selected="selected"';
                          }
                          echo '<option  value="'.$value.'" '.$selected.'>$'.$key.'</option>';
                        }
                      
                  echo '</select>
                </div><!--span2-->

                <div class="span2">';

                if(!empty($_GET['GarageSpaces']))
                {
                   echo '<label class="labelgarage">Garage</label>';
                }else{
                  echo '<label class="labelgarage"></label>';
                }
                

           echo '<select class="input-block-level quick" name="GarageSpaces" id="GarageSpaces" onChange="togglelabel(\'labelgarage\',\'Garage\',this.value)">';
                    
                    $baths = array('1+'=>'1','2+'=>'2','3+'=>'3','4+'=>'4');   
                        
                    if(!empty($_GET['GarageSpaces']))
                    {
                      echo ' <option value="0">Garage</option>';
                    }else{
                      echo ' <option value="0" selected="selected">Garage</option>';
                    }
                    foreach ($beds as $key => $value) {
                      $selected = "";
                      if(!empty($_GET['GarageSpaces']) && $_GET['GarageSpaces'] == $value)
                      {
                        $selected = 'selected="selected"';
                      }
                      echo '<option  value="'.$value.'" '.$selected.'>'.$key.'</option>';
                    }
                 echo '</select>
                </div>
                
                <div class="span3 flexmls_connect__sr_details_buttons">
                <button class="span4 btn btn-primary btn-large" id="headerchange"><i class="icon-search"></i> Search Property</button>&nbsp;';

                

              // if(isset($_GET['MLSAreaMinor']) && isset($_GET['PropertyType']) && isset($_GET['beds']) && isset($_GET['baths']) && isset($_GET['min']) && isset($_GET['max']) && isset($_GET['GarageSpaces']))

               //{
                  echo '<a style="color:#FFF;" href="#modal-login" data-toggle="ml-modal" class="hideonlogin span4 btn btn-primary btn-large my_saved_search">Save Search
                 </a>
                  <a href="#" data-toggle="modal" data-target="#myContactModal" style="color:#FFF;" class="showonlogin span4 btn btn-primary btn-large open_saved_search">Save Search</a>
                  <a style="color:#FFF;" href="#modal-login" data-toggle="ml-modal" class="hideonlogin span4 btn btn-primary btn-large my_saved_search_listing">View Saved Search
                </a>
                  <a href="http://alaskadreammakers.info/saved-search/" style="color:#FFF;" class="showonlogin span4 btn btn-primary btn-large">View Saved Search</a></div>';
               //}
                if(!empty($_GET['current_url']))
                {
                  echo '<input type="hidden" name="current_url" value="'.$_GET['current_url'].'">';
                }else{
                  $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://"; 
                  $current_url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                  echo '<input type="hidden" name="current_url" value="'.$current_url.'">';
                }   

              echo '</div>';
    echo '</form>';

  }

  function advanced_search(){    
     echo "<form  method='get' class='page-menu' style='margin-bottom: 30px;
        height: auto;
        padding: 10px 10px 10px 10px;''>
        <input type='hidden' name='alaskaSearch' value='advance'>
    <div class='flexmls_connect__search_new_title' style='color: #000000; 
          font-family: Arial, sans-serif;'>
    </div>
    <h1 class='span2' style='width:100%;'>Advance Search &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='?alaskaSearch=quick' style='color:#234c7a;'>Quick Search</a></h1><br><br>
    <div class='flexmls_connect__search_field'>
    <label>Location</label>
    <input type='text' data-connect-url='https://www.flexmls.com' class='' autocomplete='off' value='City, Zip, Address or Other Location'/>
    </div>
    <div class='flexmls_connect__search_new_min_max flexmls_connect__search_new_field_group'>
    <div class='flexmls_connect__search_field' data-connect-type='number' data-connect-field='Baths'>
    <label class='flexmls_connect__search_new_label' for='MinBaths'>
    Bathrooms </label>";
    echo "<input type='text' class='text' value='' name='MinBaths' id='1470201404-MinBaths' data-connect-default=''/>
    <span class='flexmls_connect__search_new_to'>to</span>
    <input type='text' class='text' value='' name='MaxBaths' id='1470201404-MaxBaths' data-connect-default=''/>
    </div>
    <div class='flexmls_connect__search_field' data-connect-type='number' data-connect-field='Year'>
    <label class='flexmls_connect__search_new_label' for='MinYear'>
    Year Built </label>
    <input type='text' class='text' value='' name='MinYear' id='234884906-MinYear' data-connect-default=''/>
    <span class='flexmls_connect__search_new_to'>to</span>
    <input type='text' class='text' value='' name='MaxYear' id='234884906-MaxYear' data-connect-default=''/>
    </div>
    <div class='flexmls_connect__search_field' data-connect-type='number' data-connect-field='Sqft'>
    <label class='flexmls_connect__search_new_label' for='MinSqFt'>
    Square Feet </label>
    <input type='text' class='text' value='' name='MinSqFt' id='1103713623-MinSqFt' data-connect-default=''/>
    <span class='flexmls_connect__search_new_to'>to</span>
    <input type='text' class='text' value='' name='MaxSqFt' id='1103713623-MaxSqFt' data-connect-default=''/>
    </div>
    <div class='flexmls_connect__search_field' data-connect-type='number' data-connect-field='Price'>
    <label class='flexmls_connect__search_new_label' for='MinPrice'>
    Price Range </label>
    <input type='text' class='text' value='' name='MinPrice' id='1440588890-MinPrice' data-connect-default='' onChange='this.value =  this.value.replace(/,/g,'').replace(/\$/g,'')'/>
    <span class='flexmls_connect__search_new_to'>to</span>
    <input type='text' class='text' value='' name='MaxPrice' id='1440588890-MaxPrice' data-connect-default='' onChange='this.value =  this.value.replace(/,/g,'').replace(/\$/g,'')'/>
    </div>
    </div>
    <div class='flexmls_connect__search_field flexmls_connect__search_new_property_type 
        flexmls_connect__search_new_field_group'>
        <label class='flexmls_connect__search_new_label'>Property Type</label><br>
        <select class='input-block-level quick' name='PropertyType' id='PropertyType'>
                      <option value='0' selected='selected'>PropertyType</option>
                 <option  value='A'>Residential</option>
    <option value='B'>Condominium</option>
    <option value='C'>Vacant Land</option>
    <option value='D'>Multi-Family</option>
    <option value='E'>Commercial</option>
    <option value='F'>Business Op</option>
    <option value='G'>Commercial Lease</option>
    <option value='H'>Residential Lease</option>
    <option value='I'>Mobile Homes</option>
                            </select>
    <div id='flexmls_connect__search_new_subtypes_for_B' class='flexmls_connect__search_new_subtypes'>
    <label class='flexmls_connect__search_new_label'>Property Sub Types</label>
    <input type='checkbox' name='PropertySubType[]' value='Apartment' class='flexmls_connect__search_new_checkboxes'>
    Apartment<br>
    <input type='checkbox' name='PropertySubType[]' value='Patio' class='flexmls_connect__search_new_checkboxes'>
    Patio<br>
    <input type='checkbox' name='PropertySubType[]' value='Ranch' class='flexmls_connect__search_new_checkboxes'>
    Ranch<br>
    <input type='checkbox' name='PropertySubType[]' value='Townhouse' class='flexmls_connect__search_new_checkboxes'>
    Townhouse<br>
    </div>
    <div id='flexmls_connect__search_new_subtypes_for_F' class='flexmls_connect__search_new_subtypes'>
    <label class='flexmls_connect__search_new_label'>Property Sub Types</label>
    <input type='checkbox' name='PropertySubType[]' value='Building' class='flexmls_connect__search_new_checkboxes'>
    Building<br>
    <input type='checkbox' name='PropertySubType[]' value='Land' class='flexmls_connect__search_new_checkboxes'>
    Land<br>
    <input type='checkbox' name='PropertySubType[]' value='Land & Building' class='flexmls_connect__search_new_checkboxes'>
    Land & Building<br>
    <input type='checkbox' name='PropertySubType[]' value='No Real Property' class='flexmls_connect__search_new_checkboxes'>
    No Real Property<br>
    </div>
    <div id='flexmls_connect__search_new_subtypes_for_E' class='flexmls_connect__search_new_subtypes'>
    <label class='flexmls_connect__search_new_label'>Property Sub Types</label>
    <input type='checkbox' name='PropertySubType[]' value='Mixed Use' class='flexmls_connect__search_new_checkboxes'>
    Mixed Use<br>
    <input type='checkbox' name='PropertySubType[]' value='Single Use' class='flexmls_connect__search_new_checkboxes'>
    Single Use<br>
    </div>
    <div id='flexmls_connect__search_new_subtypes_for_G' class='flexmls_connect__search_new_subtypes'>
    <label class='flexmls_connect__search_new_label'>Property Sub Types</label>
    <input type='checkbox' name='PropertySubType[]' value='Mixed Use' class='flexmls_connect__search_new_checkboxes'>
    Mixed Use<br>
    <input type='checkbox' name='PropertySubType[]' value='Single Use' class='flexmls_connect__search_new_checkboxes'>
    Single Use<br>
    </div>
    </div>
    <div class='flexmls_connect__search_field flexmls_connect__search_new_sort_by 
            flexmls_connect__search_new_field_group'>
    <label>Sort By</label><br>
    <select name='OrderBy' size='1'>
    <option value='-ListPrice'>List price (High to Low)</option>
    <option value='ListPrice'>List price (Low to High)</option>
    <option value='-BedsTotal'># Bedrooms</option>
    <option value='-BathsTotal'># Bathrooms</option>
    <option value='-YearBuilt'>Year Built</option>
    <option value='-BuildingAreaTotal'>Square Footage</option>
    <option value='-ModificationTimestamp'>Recently Updated</option>
    </select>
    </div>
    <div class='flexmls_connect__search_field flexmls_connect__search_new_field_group'>
    <label class='flexmls_connect__search_new_label'>Listing Status</label><br>
    <select name='StandardStatus' size='1'>
    <option value='Active' selected>Active</option>
    <option value='Closed'>Closed</option>
    </select>
    </div>";
    echo "<div style='visibility:hidden;' class='query'></div><input type='hidden' class='flexmls_connect__tech_id' value='x'20051203024832630723000000''/><input type='hidden' class='flexmls_connect__ma_tech_id' value='x'20040823195642954262000000''/><div class='flexmls_connect__search_new_links'><input class='flexmls_connect__search_new_submit' type='submit' value='Search For Property' style='width:20%;background:#59cfeb !important; color: #000000 !important;text-shadow: 0 1px 1px #eee !important;box-shadow: 0 1px 1px #111 !important; -webkit-box-shadow: 0 1px 1px #111 !important; -moz-box-shadow: 0 1px 1px #111 !important;background: -moz-linear-gradient(top, #7ad9ef 0%, #8bddf1 50%, #4fc5e1 51%, #3bb1cd 100%) !important;background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#7ad9ef), color-stop(50%,#8bddf1), color-stop(51%,#4fc5e1), color-stop(100%,#3bb1cd)) !important;background: -webkit-linear-gradient(top, #7ad9ef 0%,#8bddf1 50%,#4fc5e1 51%,#3bb1cd 100%) !important;background: -o-linear-gradient(top, #7ad9ef 0%,#8bddf1 50%,#4fc5e1 51%,#3bb1cd 100%) !important;background: -ms-linear-gradient(top, #7ad9ef 0%,#8bddf1 50%,#4fc5e1 51%,#3bb1cd 100%) !important;background: linear-gradient(top, #7ad9ef 0%,#8bddf1 50%,#4fc5e1 51%,#3bb1cd 100%) !important;'/></div>
</form>";
 
  }


  function generate_page($from_shortcode = false) { 
   
    if(!empty($_GET['alaskaSearch']) && $_GET['alaskaSearch'] == 'advance'){
      $this->advanced_search();
    }else{  
      $this->quick_search();
    }

    global $fmc_api;
    global $fmc_api_portal;
    global $fmc_special_page_caught;
    global $fmc_plugin_url;
    global $fmc_search_results_loaded;

    if ($this->type == 'fmc_vow_tag' && !$fmc_api_portal->is_logged_in()){
      return "Sorry, but you must <a href={$fmc_api_portal->get_portal_page()}>log in</a> to see this page.<br />";
    }
    if ($fmc_search_results_loaded and !flexmlsConnect::allowMultipleLists()) {
      return '<!-- sparkmls blocked duplicate search results widget on page -->';
    }
    $fmc_search_results_loaded = true;

    ob_start();
    flexmlsPortalPopup::popup_portal('search_page');

    $options = get_option('fmc_settings');
    $primary_details = array_merge(array('MlsStatus' =>'Status'), $options['search_results_fields']);



    $exclude_property_type = false;
    $exclude_county = false;
    $exclude_area = false;

    echo "<h1>".$this->title."</h1>";

    if ( array_key_exists('PropertyType', $this->field_value_count) && $this->field_value_count['PropertyType'] == 1) {
      $exclude_property_type = true;
    }

    if ( array_key_exists('MLSAreaMajor', $this->field_value_count) && $this->field_value_count['MLSAreaMajor'] == 1) {
      $exclude_area = true;
    }

    if ( array_key_exists('CountyOrParish', $this->field_value_count) && $this->field_value_count['CountyOrParish'] == 1) {
      $exclude_county = true;
    }

  $has_map = isset( $_GET['view'] ) && 'map' == $_GET['view'] ? ' has-map' : '';
    ?>

    <div class='flexmls_connect__page_content<?php echo esc_attr( $has_map ); ?>'>

    <?php
    /**
     * Map/List View Toggle
     */
    if ( isset( $options['google_maps_api_key'] ) && $options['google_maps_api_key'] ) :
      if ( isset( $_GET['view'] ) && 'map' == $_GET['view'] ) {
        $map_class = ' active';
        $list_class = '';
      } else {
        $map_class = '';
        $list_class = ' active';
      }
      $link = flexmlsConnect::make_nice_tag_url( 'search', $this->search_criteria );
      ?>
      <div class="flexmls_toggle-view">
      <a href="<?php echo esc_url( $link ); ?>" alt="Toggle List View" class="list-view<?php echo esc_attr( $list_class ); ?>">List View</a>
        <a href="<?php echo esc_url( add_query_arg( 'view', 'map', $link ) ); ?>" alt="Toggle Map View" class="map-view<?php echo esc_attr( $map_class ); ?>">Map View</a>
      </div>
    <?php endif; ?>
    <div class="search-list-top-head">
    
        <div class="first-pro-heading">
        <?php if(empty($_GET['alaskaSearch'])) { ?>
          <h1><?php the_title(); ?></h1>
        <?php } ?>
        </div>
     
      <div class='flexmls_connect__sr_matches'>
        <span class='flexmls_connect__sr_matches_count'>
          <?php
            echo number_format($this->total_rows, 0, '.', ',');
            ?>
        </span>
        matches found
      </div>
      

      <div class='flexmls_connect__sr_view_options'>
      <div class="flexmls_connect__sr_view_options_inner clearfix">
        <select class="flexmls_connect_select listingsperpage flexmls_connect_hasJavaScript">
          <option value="'.$this->page_size.'">Listings per page</option>
          <option value="5">5</option>
          <option value="10">10</option>
          <option value="15">15</option>
          <option value="20">20</option>
          <option value="25">25</option>
        </select>
        <select name='OrderBy' class='flexmls_connect_select flex_orderby  flexmls_connect_hasJavaScript'>
          <option>Sort By</option>
          <option value='-ListPrice'>List price (High to Low)</option>
          <option value='ListPrice'>List price (Low to High)</option>
          <option value='-BedsTotal'># Bedrooms</option>
          <option value='-BathsTotal'># Bathrooms</option>
          <option value='-YearBuilt'>Year Built</option>
          <option value='-BuildingAreaTotal'>Square Footage</option>
          <option value='-ModificationTimestamp'>Recently Updated</option>
        </select>
      </div>
      </div>
    </div>

      <hr class='flexmls_connect__sr_divider'>

    <?php
    if ( isset ( $options['google_maps_api_key'] ) && $options['google_maps_api_key'] ) :
      /**
       * Grab the proper data for the Google Map and render it.
       */
      if ( isset( $_GET['view'] ) && 'map' === $_GET['view'] ) {
        $markers      = array();
        $result_count = 0;

        foreach ( $this->search_data as $record ) {

          $result_count ++;
          $fields = $record['StandardFields'];

          if ( ! flexmlsConnect::is_not_blank_or_restricted( $fields['Latitude'] ) || ! flexmlsConnect::is_not_blank_or_restricted( $fields['Longitude'] ) ) {
              continue;
          }

          $listing_address          = flexmlsConnect::format_listing_street_address( $record );
          $first_line_address       = htmlspecialchars( $listing_address[0] );
          $second_line_address      = htmlspecialchars( $listing_address[1] );
          $link_to_details_criteria = $this->search_criteria;

          $list_price = flexmlsConnect::is_not_blank_or_restricted( $fields['ListPrice'] ) ? '$' . flexmlsConnect::gentle_price_rounding( $fields['ListPrice'] ) : '';

          $this_result_overall_index = ( $this->page_size * ( $this->current_page - 1 ) ) + $result_count;

          // figure out if there's a previous listing
          $link_to_details_criteria['p'] = ( $this_result_overall_index != 1 ) ? 'y' : 'n';

          // figure out if there's a next listing possible
          $link_to_details_criteria['n'] = ( $this_result_overall_index < $this->total_rows ) ? 'y' : 'n';

          $link_to_details = flexmlsConnect::make_nice_address_url( $record, $link_to_details_criteria, $this->type );

          // Image
          $image_thumb = '';
          $image_alt   = '';
          if ( count( $fields['Photos'] ) >= 1 ) {
            //Find primary photo and assign it to thumbnail
            foreach ( $fields['Photos'] as $key => $photo ) {
              if ( true !== $photo['Primary'] ) {
                continue;
              }
              $image_thumb = $photo['Uri300'];
              $image_alt   = $photo['Name'];
            }
          }

          $markers[] = array(
            'latitude'  => esc_html( $fields['Latitude'] ),
            'longitude' => esc_html( $fields['Longitude'] ),
            'listprice' => esc_html( $list_price ),
            'rawprice'  => esc_html( $fields['ListPrice'] ),
            'address1'  => esc_html( $first_line_address ),
            'address2'  => esc_html( $second_line_address ),
            'link'      => esc_url( $link_to_details ),
            'image'     => esc_url( $image_thumb ),
            'imagealt'  => esc_html( $image_alt ),
            'bedrooms'  => esc_html( $fields['BedsTotal'] ),
            'bathrooms' => esc_html( $fields['BathsTotal'] ),
          );
        }

        $map = new flexmlsListingMap( $markers );
        $map->render_map();
      }
    endif;

    /**
     * Display the Listings.
     */
    $result_count = 0;
    if(!empty($this->search_data)) {
    foreach ($this->search_data as $record) {
      //echo "<pre>"; print_r($record); die;
      $result_count++;
      // Establish some variables
      $listing_address = flexmlsConnect::format_listing_street_address($record);
      $first_line_address = htmlspecialchars($listing_address[0]);
      $second_line_address = htmlspecialchars($listing_address[1]);
      $one_line_address = htmlspecialchars($listing_address[2]);
      $link_to_details_criteria = $this->search_criteria;

      $this_result_overall_index = ($this->page_size * ($this->current_page - 1)) + $result_count;

      $sf =& $record['StandardFields'];


      // figure out if there's a previous listing
      $link_to_details_criteria['p'] = ($this_result_overall_index != 1) ? 'y' : 'n';

      //$link_to_details_criteria['m'] = $sf['MlsId'];

      // figure out if there's a next listing possible
      $link_to_details_criteria['n'] = ( $this_result_overall_index < $this->total_rows ) ? 'y' : 'n';

      $link_to_details = flexmlsConnect::make_nice_address_url($record, $link_to_details_criteria, $this->type);

      $rand = mt_rand();


      // Container
      echo "<div class='flexmls_connect__sr_result' title='{$one_line_address} - MLS# {$sf['ListingId']}'
        link='{$link_to_details}'>";

     

      // begin left column

      echo "<div class='flexmls_connect__sr_left_column'>";

      // Image
      if ( count($sf['Photos']) >= 1 ) {
        //Find primary photo and assign it to $main_photo_url variable.
         $count_photos = count($sf['Photos']);

        $i = 0;
        while($i < $count_photos){
          if($sf['Photos'][$i]['Primary'] === TRUE){
            $main_photo_url =     $sf['Photos'][$i]['Uri300'];
            $main_photo_urilarge = $sf['Photos'][$i]['UriLarge'];
            $caption = htmlspecialchars($sf['Photos'][$i]['Caption'], ENT_QUOTES);
            break;
          }
          $i++;
        }

      } else {
        // $photos = $fmc_api->GetListingPhotos($record['Id']);
        // $count_photos = count($photos);
        // $i = 0;
        // while($i < $count_photos){
        //   if($photos[$i]['Primary'] === TRUE){
        //     $main_photo_url =     $photos[$i]['Uri300'];
        //     $main_photo_urilarge = $photos[$i]['UriLarge'];
        //     $caption = htmlspecialchars($photos[$i]['Caption'], ENT_QUOTES);
        //     break;
        //   }
        //   $i++;
        // }
        
        if(empty($main_photo_url))
        {
          $main_photo_url = "{$fmc_plugin_url}/assets/images/nophoto.gif";
          $main_photo_urilarge = "{$fmc_plugin_url}/assets/images/nophoto.gif";
          $caption = "";
        }
        
      }
    //set alt value
    if(!empty($caption)){
      $img_alt_attr = $caption;
    }
    elseif(!empty($one_line_address)){
      $img_alt_attr = $one_line_address;
    }
    else{
      $img_alt_attr = "Photo for listing #" . $sf['ListingId'];
    }

    //set title value
    $img_title_attr = "Photo for ";
    if(!empty($one_line_address)){
      $img_title_attr .= $one_line_address . " - ";
    }

    $img_title_attr .= "listing #" . $sf['ListingId'];

      echo "<a class='photo' href='{$main_photo_urilarge}' rel='{$rand}-{$sf['ListingKey']}' title='{$caption}'>";
      echo "<img class='flexmls_connect__sr_main_photo' src='{$main_photo_url}' onerror='this.src=\"{$fmc_plugin_url}/assets/images/nophoto.gif\"' alt='{$img_alt_attr}' title='{$img_title_attr}' />";
      echo "</a>";
      echo "<div class='flexmls_connect__hidden'></div>";
      echo "<div class='flexmls_connect__hidden2'></div>";
      echo "<div class='flexmls_connect__hidden3'></div>";


      // Detail Links
      $count_photos = count($sf['Photos']);
      $count_videos = count($sf['Videos']);
      $count_tours = count($sf['VirtualTours']);

      echo "<div class='flexmls_connect__sr_details'>";

      fmcAccount::write_carts($record);

      

      if ($count_photos > 0)
        echo "<a class='photo_click flexmls_connect__sr_asset_link'>View Photos ({$count_photos})</a>";
      if ($count_videos > 0)
        echo "<a class='video_click flexmls_connect__sr_asset_link' rel='v{$rand}-{$sf['ListingKey']}'>Videos ({$count_videos})</a>";
      if ($count_tours > 0)
        echo "<a class='tour_click flexmls_connect__sr_asset_link' rel='t{$rand}-{$sf['ListingKey']}'>Virtual Tours ({$count_tours})</a>";

      echo "</div>";

      // end flexmls_connect__sr_left_column
      echo "</div>";

       // Price
      echo "<div class='flexmls_connect__sr_price'>";
        if(flexmlsConnect::is_not_blank_or_restricted($sf['ListPrice'])) echo '$' . flexmlsConnect::gentle_price_rounding($sf['ListPrice']);
      echo "</div>";

      // Address
      echo "<div class='flexmls_connect__sr_address'>";
        echo "<a href='{$link_to_details}' title='Click for more details'>";
          if ($first_line_address) {
            echo $first_line_address.' ';
            //if ($second_line_address)
              //echo "<br />";
          }
          echo $second_line_address;
        echo "</a>";
      echo "</div>";

      // Details table
      echo "<div class='flexmls_connect__sr_listing_facts_container'>";

      // Open House
      if ( count($sf['OpenHouses']) >= 1) {
        // echo "<div class='flexmls_connect__sr_openhouse'>";
        //   echo "<em>Open House</em> ({$sf['OpenHouses'][0]['Date']} - {$sf['OpenHouses'][0]['StartTime']})";
        // echo "</div>";
      }

      echo "<div class='flexmls_connect__sr_listing_facts'>";
      $first = "";
      $second = "";
       $second = "<div class='flexmls_connect__zebra'><span class='flexmls_connect__field_label'>Garage: </span>";
       if(!empty($sf['GarageSpaces']))
       {
        $second .=  "<span class='flexmls_connect__field_value'>{$sf['GarageSpaces']}</span></div>";
       }else{
        $second .=  "<span class='flexmls_connect__field_value'>0</span></div>";
       }
      

      //display listing status if it is not active
      $detail_count = 0; //print_r($primary_details);
      foreach ($primary_details as $field_id => $display_name) {
        // if ($field_id == 'PropertyType' and $exclude_property_type) {
        //   continue;
        // }
        if ($field_id == 'MLSAreaMajor' and $exclude_area) {
          continue;
        }
        if ($field_id == 'CountyOrParish' and $exclude_county) {
          continue;
        }
        if ($field_id == 'MlsStatus' and $sf[$field_id] == 'Active'){
          continue;
        }

        $zebra = (flexmlsConnect::is_odd($detail_count)) ? 'on' : 'off';
        if($display_name != 'Subdivision')
        {

        //if ( flexmlsConnect::is_not_blank_or_restricted( $sf[$field_id] ) ) {
          $this_val = $sf[$field_id];

          if ($field_id == "PropertyType") {
            $this_val = flexmlsConnect::nice_property_type_label($this_val);
          }

          if ($field_id == "PublicRemarks") {
            $this_val = substr($this_val, 0, 75) . "...";
          }
          if ($field_id == 'MlsStatus' and $sf[$field_id] == 'Closed'){
            $this_val = "<span style='color:Blue;font-weight:bold'>$this_val</span>";
          }
          elseif ($field_id == 'MlsStatus') {
              $this_val = "<span style='color:Orange;font-weight:bold'>$this_val</span>";
          }

          $detail_count++;

          
         
          if($display_name == 'Description')
          {
            $description =  "<div class='flexmls_connect__zebra flexmls_connect__zebra_description'><span class='flexmls_connect__field_label flexmls_connect__field_description'>{$display_name} </span>";
            if(is_array($this_val)){
             $this_val =  $this_val = implode(", ", array_keys($this_val));
            }

             $description .= "<span class='flexmls_connect__field_value'>{$this_val}</span></div>";
          }else if($display_name == 'Area')
          {
            // echo  "<div class='flexmls_connect__zebra flexmls_connect__zebra_description'><span class='flexmls_connect__field_label flexmls_connect__field_description'>{$display_name} </span>";
            // if(is_array($this_val)){
            //  $this_val =  $this_val = implode(", ", array_keys($this_val));
            // }

            //  echo "<span class='flexmls_connect__field_value'>{$this_val}</span></div>";
          }else {
            if($display_name == 'Property Type' || $field_id == 'PropertyType')
            {
               $first .= "<div class='flexmls_connect__zebra'><span class='flexmls_connect__field_label'>Type: </span>";
               if(is_array($this_val)){
                $this_val = implode(", ", array_keys($this_val));
               }
               $first .= "<span class='flexmls_connect__field_value'>{$this_val}</span></div>";
            }else if($display_name == 'Square Footage')
            {
               $first .= "<div class='flexmls_connect__zebra'><span class='flexmls_connect__field_label'>SQ FT: </span>";
               if(is_array($this_val)){
                $this_val = implode(", ", array_keys($this_val));
               }
               $first .= "<span class='flexmls_connect__field_value'>{$this_val}</span></div>";
             }else if($display_name == 'Year Built')
            {
               $first .= "<div class='flexmls_connect__zebra'><span class='flexmls_connect__field_label'>Year Built: </span>";
               if(is_array($this_val)){
                $this_val = implode(", ", array_keys($this_val));
               }
               $first .= "<span class='flexmls_connect__field_value'>{$this_val}</span></div>";
             }else if($display_name == '# of Bedrooms'){
                $second .=  "<div class='flexmls_connect__zebra'><span class='flexmls_connect__field_label'>Bedrooms: </span>";
                if(is_array($this_val)){
                  $this_val = implode(", ", array_keys($this_val));
                 }
                 $second .=  "<span class='flexmls_connect__field_value'>{$this_val}</span></div>";
             }else if($display_name == '# of Bathrooms'){
                $second .=  "<div class='flexmls_connect__zebra'><span class='flexmls_connect__field_label'>Bathrooms: </span>";
                 if(is_array($this_val)){
                  $this_val = implode(", ", array_keys($this_val));
                 }
                 $second .=  "<span class='flexmls_connect__field_value'>{$this_val}</span></div>";
             }else{
                echo "<div class='flexmls_connect__zebra'><span class='flexmls_connect__field_label'>{$display_name}: </span>";
                 if(is_array($this_val)){
                  $this_val = implode(", ", array_keys($this_val));
                 }
                 echo "<span class='flexmls_connect__field_value'>{$this_val}</span></div>";
             }            
            
          }
          

          }

        //}

      }

      echo $first;
      echo $second;
      echo $description; 

      

      $compList = flexmlsConnect::mls_required_fields_and_values("Summary",$record);
      foreach ($compList as $reqs){
        $zebra = (flexmlsConnect::is_odd($detail_count)) ? 'on' : 'off';
        if (flexmlsConnect::is_not_blank_or_restricted($reqs[1])){
          if ($reqs[0]=='LOGO'){
            echo "<div class='flexmls_connect__zebra flexmls_connect__zebra_image'>";
            echo "<span class='flexmls_connect__sr_idx'>";
            if ($reqs[1]=='IDX'){
              echo "<span style='float: right' class='flexmls_connect__badge' title='{$sf['ListOfficeName']}'>IDX</span>";
            }
            else {
              echo "<img class='flexmls_connect__badge flexmls_connect_badge_img'  src='{$reqs[1]}' />";
            }
            echo '</span>';
            echo '</div>';
            $detail_count++;
            continue;
          }
          if($reqs[0] != 'Last Updated')
          {
              echo  "<div class='flexmls_connect__zebra flexmls_connect__zebra_listing_office'><span class='flexmls_connect__field_label flexmls_connect__field_listing_office'>{$reqs[0]}: </span><span class='flexmls_connect__field_value'>{$reqs[1]}</span></div>";
               $detail_count++;
          }          
        }
      }
 
      // end table
      echo "</div></div>";  
       echo "<div style='display:none;color:green;font-weight:bold;text-align:left;padding:5px'
        id='flexmls_connect__success_message{$sf['ListingId']}'></div>";

      echo "<div class='flexmls_connect__sr_details_buttons'>";
        echo "<button href='{$link_to_details}'>View Details</button>";
        ?>
        <button onclick="flexmls_connect.contactForm({
          'title': 'Ask a Question',
          'subject': '<?php echo addslashes($one_line_address); ?> - MLS# <?php echo addslashes($sf['ListingId'])?> ',
          'agentEmail': '<?php echo $this->contact_form_agent_email($sf); ?>',
          'officeEmail': '<?php echo $this->contact_form_office_email($sf); ?>',
          'listingId': '<?php echo addslashes($sf['ListingId']); ?>'
          });">
          Ask Question
        </button>
        <?php
      echo "</div>"; 
      // end flexmls_connect__sr_listing_facts_container
      echo "</div>"; //die;
    } 
  }



    echo "<hr class='flexmls_connect__sr_divider'>";

    if ($this->total_pages != 1) {
      echo $this->pagination($this->current_page, $this->total_pages);
    }

    echo "  <div class='flexmls_connect__idx_disclosure_text flexmls_connect__disclaimer_text'>";
    echo flexmlsConnect::get_big_idx_disclosure_text();
    echo "</div>";

    echo '</div> ';

    echo '<div class="modal fade" id="myContactModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
      <div class="modal-dialog modal-sm">
            <div class="modal-content">
               <h3>Search details:</h3>
               <ul id="search_details">
               </ul>
                <b> Notified when:</b>
                <div class="lsitingnotification-outer">
              <p class="label-text-for-notification">New Listing</p>
                 <p class="electric"><input id="newlisting" class="email_send_on" type="checkbox"  name="emailon_newlisting" value="1"  checked /><label for="newlisting"> YES</label></p>
              </div>
              <div class="lsitingnotification-outer">
              <p class="label-text-for-notification">Price Change</p>
                 <p class="electric"><input id="price" type="checkbox" class="email_send_on"  name="emailon_pricechange" value="1" /><label for="price">  NO</label></p>
              </div>
             <div class="lsitingnotification-outer">
              <p class="label-text-for-notification">Status Change</p>
                 <p class="electric"><input id="status" type="checkbox" class="email_send_on"   name="emailon_statuschange" value="1"   /><label for="status">NO</label></p>
              </div>
            <!--   <ul>             
              <li><input type="radio" name="alert_triggers" value="0" checked /><lebal style="text-align:right;">New Listings</lebal></li>
              <li><input type="radio" name="alert_triggers" value="1" /><lebal style="text-align:right;">Price Change</lebal></li>
              <li><input type="radio" name="alert_triggers" value="2" /><lebal style="text-align:right;">Status Change</lebal></li>
              </ul> -->
              <b></b>
              <b>Email notification:</b>
               <div class="lsitingnotification-outer">
             <p class="label-text-for-notification-time">Once a day </p>
               <p class="electric"><input id="onceday"  class="onetime_emailchange" type="radio"  name="frequency_management" value="0"  checked /><label for="onceday">YES</label></p>
               </div>
               <div class="lsitingnotification-outer">
                <p class="label-text-for-notification-time">As soon as possible</p>
               <p class="electric"><input id="asap" type="radio"  class="onetime_emailchange"   name="frequency_management" value="1"  /><label for="asap">NO</label></p>
               </div>
            <!--    <ul>
              <li><input type="radio" name="frequency_management" value="0" /><lebal style="text-align:right;"> Once a day</lebal> </li>
              <li><input type="radio" name="frequency_management" value="1" checked /><lebal style="text-align:right;">As soon as possible</lebal></li>
              </ul> -->
               <input type="text" id="save_search_name" placeholder="Name your search" />
               <button id="save_search">Save</button>
            </div>
         </div>
    </div>';

    // echo '<div id="modal-login" class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-hidden="false" style="display: block;">
    //          <div class="modal-dialog modal-sm">
    //         <div class="modal-content">
    //            <h3>Search details:</h3>
    //            <ul id="search_details">
    //            </ul>
    //             <b> Notified when:</b>
    //             <div class="lsitingnotification-outer">
    //           <p class="label-text-for-notification">New Listing</p>
    //              <p class="electric"><input id="newlisting" class="email_send_on" type="checkbox"  name="emailon_newlisting" value="1"  checked /><label for="newlisting"> YES</label></p>
    //           </div>
    //           <div class="lsitingnotification-outer">
    //           <p class="label-text-for-notification">Price Change</p>
    //              <p class="electric"><input id="price" type="checkbox" class="email_send_on"  name="emailon_pricechange" value="1" /><label for="price{{search.id}}">  NO</label></p>
    //           </div>
    //          <div class="lsitingnotification-outer">
    //           <p class="label-text-for-notification">Status Change</p>
    //              <p class="electric"><input id="status" type="checkbox" class="email_send_on"   name="emailon_statuschange" value="1"   /><label for="status">NO</label></p>
    //           </div>
    //         <!--   <ul>             
    //           <li><input type="radio" name="alert_triggers" value="0" checked /><lebal style="text-align:right;">New Listings</lebal></li>
    //           <li><input type="radio" name="alert_triggers" value="1" /><lebal style="text-align:right;">Price Change</lebal></li>
    //           <li><input type="radio" name="alert_triggers" value="2" /><lebal style="text-align:right;">Status Change</lebal></li>
    //           </ul> -->
    //           <b>Email notification:</b>
    //            <div class="lsitingnotification-outer">
    //           <p class="label-text-for-notification-time">Once a day </p>
    //            <p class="electric"><input id="onceday"  class="onetime_emailchange" type="radio"  name="frequency_management" value="0"  checked /><label for="onceday">YES</label></p>
    //            </div>
    //            <div class="lsitingnotification-outer">
    //             <p class="label-text-for-notification-time">As soon as possible</p>
    //            <p class="electric"><input id="asap" type="radio"  class="onetime_emailchange"   name="frequency_management" value="1"  /><label for="asap">NO</label></p>
    //            </div>
    //         <!--    <ul>
    //           <li><input type="radio" name="frequency_management" value="0" /><lebal style="text-align:right;"> Once a day</lebal> </li>
    //           <li><input type="radio" name="frequency_management" value="1" checked /><lebal style="text-align:right;">As soon as possible</lebal></li>
    //           </ul> -->
    //            <input type="text" id="save_search_name" placeholder="Name your search" />
    //            <button id="save_search">Save</button>
    //         </div>
    //      </div>
    //   </div>';

    echo '<!-- Modal For save search -->
<div class="modal fade" id="showmodalonsendemailchange">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title response-header"></h4>
      </div>
      <div class="modal-body response-body">
         
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->';

    $content = ob_get_contents();
    ob_end_clean();

    return $content;
  }

  function pagination($current_page, $total_pages) {

    $jump_after_first = false;
    $jump_before_last = false;

    $tolerance = 5;

    $return = " <div class='flexmls_connect__sr_pagination'>";

    if ($current_page != 1) {
      $return .= "    <button href='". $this->make_pagination_link($current_page - 1) ."'>Previous</button>";
    }

    if ( ($current_page - $tolerance - 1) > 1 ) {
      $jump_after_first = true;
    }

    if ( $total_pages > ($current_page + $tolerance + 1) ) {
      $jump_before_last = true;
    }


    for ($i = 1; $i <= $total_pages; $i++) {

      if ($i == $total_pages and $jump_before_last) {
        $return .= "     ... ";
      }

      $is_current = ($i == $current_page) ? true : false;
      if ($i != 1 and $i != $total_pages) {
        if ( $i < ($current_page - $tolerance) or $i > ($current_page + $tolerance) ) {
          continue;
        }
      }

      if ($is_current) {
        $return .= "    <span>{$i}</span> ";
      }
      else {
        $return .= "    <a href='". $this->make_pagination_link($i) ."'>{$i}</a> ";
      }

      if ($i == 1 and $jump_after_first) {
        $return .= "     ... ";
      }

    }

    if ($current_page != $total_pages) {
      $return .= "     <button href='". $this->make_pagination_link($current_page + 1) ."'>Next</button>";
    }
    $return .= "  </div><!-- pagination -->";

    return $return;

  }

  function make_pagination_link($page) {
      $page_conditions = $this->search_criteria;
      $page_conditions['pg'] = $page;
    $link = flexmlsConnect::make_nice_tag_url('search', $page_conditions);
    if ( isset( $_GET['view'] ) && 'map' === $_GET['view'] ) {
      $link = add_query_arg( 'view', 'map', flexmlsConnect::make_nice_tag_url( 'search', $page_conditions ) );
    }
    
      return $link;
    
  }

}