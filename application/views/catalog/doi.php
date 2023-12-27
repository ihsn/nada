<nav class="navbar navbar-dark bg-primary justify-content-between mb-5">
  <a class="navbar-brand" href="<?php echo site_url('admin/catalog/edit/'.$dataset['id']);?>"><?php echo $dataset['title'];?></a>
  
  <?php echo form_open(null,'class="form-inline"');?>   
    <a href="<?php echo site_url('admin/catalog/edit/'.$dataset['id']);?>" class="btn btn-default my-2 my-sm-0" ><i class="fas fa-arrow-alt-circle-left"></i> Return to study edit page</a>
  <?php echo form_close();?>
</nav>


<script src="https://cdn.jsdelivr.net/npm/vue@2"></script>

<div id="app" class="container-fluid">

<h1>Attach DOI</h1>

<div class="row">
  <div class="col-md-7">
    <div id="profile" role="tabpanel" aria-labelledby="profile-tab"><?php echo $this->load->view('catalog/doi_create',null,true);?></div>
  </div>

  <div class="col-md-5">
    <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab"><?php echo $this->load->view('catalog/doi_attach',null,true);?></div>
  </div>
</div> 




   

</div>



<script>

var doi_info={};

var app = new Vue({
  el: '#app',
  data: {
    doi_form:{
      'idno':'<?php echo $dataset['idno'] ;?>',
      //'doi':'<?php echo isset($doi_options['doi']) ? $dataset['doi'] : '' ;?>',
			'identifier':'<?php echo $dataset['idno'] ;?>',
			"creator":'<?php echo $doi_options['creator'] ;?>',
			"title":'<?php echo $dataset['title'] ;?>',
			"publisher":'<?php echo $doi_options['publisher'] ;?>',
			"publication_year":'<?php echo $dataset['year_start'] ;?>',
			"url":'<?php echo site_url('catalog/'.$dataset['id']);?>',
      '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
    },
    doi:'',
    doi_prefix: '<?php echo $doi_options['prefix'];?>',
    doi_validation:true,
    doi_info:doi_info,
    dataset: <?php echo json_encode($dataset);?>,
    //doi_api_url: 'https://api.test.datacite.org/dois'
    doi_api_url: '<?php echo site_url('api/doi/');?>',
    errors:'',
    api_response:{}
  },
  async mounted() {
    this.doi=this.doi_prefix + '/' + this.doi_form.idno;
    this.searchByDoi();
  },
  /*mounted: function(){
      this.search();
      //this.renderMap();
  },*/

  computed: {
    doiExists() {
      if (!this.doi_info.id){
        return false;
      }

      return true;
    }
  },
  methods:{  
    attachDoi: function(){
      let url=this.doi_api_url + 'attach_doi/' + this.doi_form.idno;
      let vm=this;
      $.ajax
        ({
            type: "POST",
            url:  url,
            //contentType: 'application/json',
            //dataType: 'json',
            //async: false,
            data: {
              'doi':vm.doi,
              '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
            },
            success: function (data) {
                alert("Updated");
                vm.api_response=data;
            },
            error: function(e){                
                console.log(e);
                alert("failed" + e);
                vm.api_response=e;
            }
        })

    },
    submitForm: function(){
      let url = this.doi_api_url;
      vm=this;

      this.doi_form.doi=this.doi_prefix + '/' + this.doi_form.idno;
      this.doi_form.id=this.doi_form.doi;
      
      $.ajax
        ({
            type: "POST",
            url:  url,
            //contentType: 'application/json',
            //dataType: 'json',
            //async: false,
            data: this.doi_form,
            success: function (data) {
                alert("Updated");
                vm.api_response=data;
            },
            error: function(e){                
                console.log(e);
                alert("failed" + e);
                vm.api_response=e;
                vm.attachDoi();
            }
        })
    },
    validateDoi: function(){
        var doi_regex=/^[a-zA-Z0-9-_./]+$/;
        this.doi_validation= doi_regex.test(this.doi);
    },
    searchByDoi: function () {
        this.is_searching=true;
        var url = this.doi_api_url;

        if (this.doi.length>0){
            url=url+this.doi;
        }

        console.log(url);

        let vm=this;

        $.ajax
        ({
            type: "GET",
            url:  url,
            contentType: 'application/json',
            dataType: 'json',
            //async: false,
            success: function (data) {
                vm.doi_info=data.body.data;
                vm.is_searching=false;                
            },
            error: function(e){
                vm.is_searching=false;
                vm.doi_info=e;
            }
        })
    }
}

    
});
</script>
