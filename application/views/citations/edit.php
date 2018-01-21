<style>
.matching-citations{
    display: block;
    padding: 9.5px;
    margin: 0 0 10px;
    font-size: 12px;
    line-height: 20px;
    background-color: #f5f5f5;
}

.suggestions .authors{
    color:gray;
}
.suggestions .pub-year{font-style:italic;}

.suggestions .citation-row{
    padding-bottom:5px;
    border-bottom:1px solid gainsboro;
    padding-top:5px;
}

.suggestions .title,
.suggestions .subtitle{
    font-weight: bold;
}

.survey-row {font-size:smaller;}
.survey-row .country {font-style:italic;}
.survey-row .survey-title{font-weight:bold;}

.sidebar-attach-studies{
    border-left:0px solid gainsboro;
    padding-left:10px;
    padding-bottom:10px;
    background:#f5f5f5;
    min-height:400px;
}

.sidebar-attach-studies .survey-title,
.sidebar-attach-studies label{
    cursor:pointer;
    display:block;
}

.sidebar-attach-studies .survey-title:hover,
.sidebar-attach-studies label:hover{
    background:rgb(250, 252, 219);
}

.sidebar-attach-studies .col1{
    position:absolute;
    left:0px;
}
.sidebar-attach-studies .col2{
    padding-left:40px;
}

.sidebar-attach-studies .survey-row{
    position:relative;
    padding-bottom:10px;
    border-bottom:1px solid gainsboro;
}

.sidebar-attach-studies .items-found{font-weight:bold;margin-top:5px;margin-bottom:10px;}

.nav-tabs li a{background:#F4F4F4}
.nav-tabs .action-buttons{float:right;}
.extra-spacing .field{padding-right:15px;}

.highlight { background-color: rgb(250, 223, 173) }

.row-<?php echo $this->uri->segment(4);?>{
    background:rgb(250, 252, 219);
    padding:5px;
    border-left:4px solid orange;
}

.row-<?php echo $this->uri->segment(4);?> .highlight{
    background:none;
}

.citation-row a{text-decoration:none;display:block;}
.citation-row a:hover{background:rgb(250, 252, 219);}
.no-citations{background:red;}

.padding-table-rt td{padding-right:10px;}
.padding-left-10{padding-left:10px;}

</style>

<div class="container-fluid page-citations-edit">
<div class="pull-right page-links"> 
  <a href="<?php echo site_url(); ?>/admin/citations/" class="btn btn-default">
    <span class="glyphicon glyphicon-home ico-add-color right-margin-5" aria-hidden="true"></span>
    <?php echo t('citation_home');?>
  </a> 
</div>

<?php
    $citation_types=array(
        'book'					=>t('Book'),
        'book-section'			=>t('Book Section'),
        'report'				=>t('report'),			//same as book
        'anthology-editor'		=>t('Anthology (Author & Editor)'),
        'anthology-translator'	=>t('Anthology (Author & Translator)'),
        'corporate-author'		=>t('corporate-author'),		//todo
        'journal'				=>t('Journal'),
        'working-paper'			=>t('working-paper'), 		//same as journal
        'conference-paper'		=>t('conference-paper'),
        'magazine'				=>t('Magazine'),
        'newspaper'				=>t('Newspaper'),
        'website'				=>t('Website'),
        'website-doc'			=>t('Website Document'),
        'thesis'				=>t('Thesis or Dissertation'),
    );

    $lang_list=array(
        ''=>'--',
        'Afrikanns'=>'Afrikanns',
        'Albanian'=>'Albanian',
        'Arabic'=>'Arabic',
        'Armenian'=>'Armenian',
        'Basque'=>'Basque',
        'Bengali'=>'Bengali',
        'Bulgarian'=>'Bulgarian',
        'Catalan'=>'Catalan',
        'Cambodian'=>'Cambodian',
        'Chinese (Mandarin)'=>'Chinese (Mandarin)',
        'Croation'=>'Croation',
        'Czech'=>'Czech',
        'Danish'=>'Danish',
        'Dutch'=>'Dutch',
        'English'=>'English',
        'Estonian'=>'Estonian',
        'Fiji'=>'Fiji',
        'Finnish'=>'Finnish',
        'French'=>'French',
        'Georgian'=>'Georgian',
        'German'=>'German',
        'Greek'=>'Greek',
        'Gujarati'=>'Gujarati',
        'Hebrew'=>'Hebrew',
        'Hindi'=>'Hindi',
        'Hungarian'=>'Hungarian',
        'Icelandic'=>'Icelandic',
        'Indonesian'=>'Indonesian',
        'Irish'=>'Irish',
        'Italian'=>'Italian',
        'Japanese'=>'Japanese',
        'Javanese'=>'Javanese',
        'Korean'=>'Korean',
        'Latin'=>'Latin',
        'Latvian'=>'Latvian',
        'Lithuanian'=>'Lithuanian',
        'Macedonian'=>'Macedonian',
        'Malay'=>'Malay',
        'Malayalam'=>'Malayalam',
        'Maltese'=>'Maltese',
        'Maori'=>'Maori',
        'Marathi'=>'Marathi',
        'Mongolian'=>'Mongolian',
        'Nepali'=>'Nepali',
        'Norwegian'=>'Norwegian',
        'Persian'=>'Persian',
        'Polish'=>'Polish',
        'Portuguese'=>'Portuguese',
        'Punjabi'=>'Punjabi',
        'Quechua'=>'Quechua',
        'Romanian'=>'Romanian',
        'Russian'=>'Russian',
        'Samoan'=>'Samoan',
        'Serbian'=>'Serbian',
        'Slovak'=>'Slovak',
        'Slovenian'=>'Slovenian',
        'Spanish'=>'Spanish',
        'Swahili'=>'Swahili',
        'Swedish '=>'Swedish ',
        'Tamil'=>'Tamil',
        'Tatar'=>'Tatar',
        'Telugu'=>'Telugu',
        'Thai'=>'Thai',
        'Tibetan'=>'Tibetan',
        'Tonga'=>'Tonga',
        'Turkish'=>'Turkish',
        'Ukranian'=>'Ukranian',
        'Urdu'=>'Urdu',
        'Uzbek'=>'Uzbek',
        'Vietnamese'=>'Vietnamese',
        'Welsh'=>'Welsh',
        'Xhosa'=>'Xhosa'
    );

    $flag_options=array(
        ''=>'--',
        'ds_unclear'=>t('ds_unclear'),
        'incomplete'=>t('incomplete'),
        'tobe_checked'=>t('tobe_checked'),
        'duplicate'=>t('duplicate'),
        'back_to_editor'=>t('back_to_editor'),
    );

    $publish_options=array(
        '1'=>t('option_publish'),
        '0'=>t('option_do_not_publish')
    );

    //get max upload/post limits
    $max_upload = (int)(ini_get('upload_max_filesize'));
    $max_post = (int)(ini_get('post_max_size'));

    $max_limit=$max_upload;

    if ($max_upload>$max_post){
        $max_limit=$max_post;
    }

?>

  <?php if (validation_errors() ) : ?>
    <div class="alert alert-danger"> <?php echo validation_errors(); ?> </div>
  <?php endif; ?>
  
  <?php $error=$this->session->flashdata('error');?>
  <?php echo ($error!="") ? '<div class="alert alert-danger">'.$error.'</div>' : '';?>
  <?php $message=$this->session->flashdata('message');?>
  <?php echo ($message!="") ? '<div class="alert alert-success">'.$message.'</div>' : '';?>

  <h1 class="page-title"><?php echo $form_title; ?></h1>
  <?php echo form_open_multipart($this->html_form_url, array('class'=>'form-horizontal form','autocomplete'=>'off') ); ?>
  <ul class="nav nav-tabs" role="tablist">
    <li class="active"> <a href="#edit" rel="tab" data-toggle="tab">Home</a></li>
    <li> <a href="#tab-attach-studies" rel="tab" data-toggle="tab">Attach Surveys <span class="badge citation-count"></span></a></li>
    <li class="pull-right action-buttons">
      <input type="submit" name="submit" id="submit" class="btn btn-primary" value="<?php echo t('Save'); ?>" />
      <span class="btn btn-default"><?php echo anchor('admin/citations/', t('cancel'));?></span> </li>
  </ul>
  <div class="tab-content" style="padding-top:15px;">
    <div class="tab-pane active" id="edit" >
      <div class="col-md-8" style="padding-right:40px;">
        <input name="survey_id" type="hidden" id="survey_id" value="<?php echo get_form_value('survey_id',isset($survey_id) ? $survey_id: ''); ?>"/>
        <input name="tmp_id" type="hidden" id="tmp_id" value="<?php echo get_form_value('tmp_id',isset($tmp_id) ? $tmp_id: 'cit-'.date("U")); ?>"/>
        
        <div class="form-group form-group-">
            <label for="ctype"><?php echo t('select_citation_type');?></label>
            <div class="form-inline">
              <?php echo form_dropdown('ctype', $citation_types, get_form_value("ctype",isset($ctype) ? $ctype : ''),array('id'=>'citation_type','class'=>'form-control')) ; ?>
              <input class="btn btn-primary" type="submit" name="select" id="change_type" value="Change type"/>
            </div>
        </div>

        
        <div id="citation-edit-view">
        <?php
        //load the citation view based on the citation view
        $citation_view=get_form_value('ctype',isset($ctype) ? $ctype: 'book');
        $citation_view=str_replace("-","_",'edit_'.$citation_view);
        //include 'edit_book.php';
        //print_r($citation_view);die;
        $this->load->view("citations/$citation_view");
        ?>
        </div>
        <div class="row doilan">
          <div class="col-md-6">
            <div class="form-group" style="padding-right:10px;">
              <label for="doi"><?php echo t('doi');?></label>
              <input name="doi" class="form-control" type="text" id="doi" size="50"  value="<?php echo get_form_value('doi',isset($doi) ? $doi : ''); ?>"/>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group field">
              <label for="lang"><?php echo t('language');?></label>
              <?php echo form_dropdown('lang', $lang_list, get_form_value("lang",isset($lang) ? $lang : ''),array('class'=>'form-control','id'=>'lang')); ?> </div>
          </div>
        </div>
        <div class="content">
          <div class="form-group">
            <label for="abstract"><?php echo t('abstract');?></label>
            <textarea name="abstract" id="abstract" rows="3" class="form-control"><?php echo get_form_value('abstract',isset($abstract) ? $abstract : ''); ?></textarea>
          </div>
        </div>
        <div class="content">
          <div class="form-group">
            <label for="keywords"><?php echo t('keywords');?></label>
            <textarea class="form-control" name="keywords" id="keywords" rows="3"><?php echo get_form_value('keywords',isset($keywords) ? $keywords : ''); ?></textarea>
          </div>
        </div>
        <div class="content">
          <div class="form-group">
            <label for="notes"><?php echo t('notes');?></label>
            <textarea class="form-control" name="notes" id="notes" rows="3"><?php echo get_form_value('notes',isset($notes) ? $notes : ''); ?></textarea>
          </div>
        </div>
        <div class="content">
          <div class="form-group">
            <fieldset class="always-visible">
              <legend><?php echo t('attach_file');?> <span class="max-file-size">(<?php echo t('max_upload_limit') ." ".$max_limit;?>MB)</span> </legend>
              <div class="file-upload-control">
                <input class="form-control" type="file" name="attachment" id="attachment" size="60"/>
                <div class="description">
                    <?php echo t('allowed_file_types');?>: <span class="file-types"><?php echo str_replace("|",", ",$this->allowed_attachment_file_types);?></span>
                </div>
                <?php if(isset($attachment) && strlen($attachment)>1):?>
                <div class="attachment-link" style="margin-top:15px;">
                  <div><strong><?php echo t('attachment');?></strong></div>
                  <div>
                  <button type="button" class="btn btn-default btn-xs">
                  <a target="_blank" class="download-link" href="<?php echo site_url('admin/citations/download_attachment/'.$id);?>"><span class="glyphicon glyphicon-download" aria-hidden="true"></span> 
                    <?php echo html_escape(basename($attachment));?></a>
                    </button>
                    <button type="button" class="btn btn-default btn-xs">  
                  <a class="delete-citation-attachment" href="<?php echo site_url('admin/citations/delete_attachment/'.$id);?>"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span> <?php echo t('delete');?></a>
                  </button>
                  </div>
                </div>
                <?php endif;?>
              </div>
            </fieldset>
          </div>
        </div>

        <div class="form-group">
        <table border="0" class="inline-fields extra-spacing" >
          <tr>
            <td style="padding-right:10px;">
                <div class="field">
                    <label for="flag"><?php echo t('flag_entry_as');?></label>
                    <?php echo form_dropdown('flag', $flag_options, get_form_value("flag",isset($flag) ? $flag : ''),array('id'=>'flag','class'=>'form-control')); ?> 
                </div>
            </td>
            <td style="padding-right:10px;">
                <div class="field">
                    <label for="publish"><?php echo t('publish_citation');?></label>
                    <?php echo form_dropdown('published', $publish_options, get_form_value("published",isset($published) ? $published : ''),array('id'=>'published','class'=>'form-control')); ?> 
                </div>
            </td>
            <!--
            <?php if(isset($created_by)):?>
            <td><div class="field">
                <label class="created-by">Created by</label>
                <div><?php echo $created_by_user;?>
                  <?php if ($created): ?>
                  <span class="dated"><?php echo date("M/d/y H:i", $created);?></span>
                  <?php endif;?>
                </div>
              </div></td>
            <?php endif;?>
            <?php if(isset($changed_by)):?>
            <td><div class="field">
                <label class="created-by">Modified by</label>
                <div><?php echo $changed_by_user;?>
                  <?php if ($changed): ?>
                  <span class="dated"><?php echo date("M/d/y H:i", $changed);?></span>
                  <?php endif;?>
                </div>
              </div></td>
            <?php endif;?>
            -->
            <input name="owner" type="hidden" id="owner" size="50" class="form-control"  value="<?php echo get_form_value('owner',isset($owner) ? $owner : $this->ion_auth->current_user()->username); ?>"/>
          </tr>
        </table>
        </div>
        
        <div class="form-group" style="margin-top:20px;">
          <input class="btn btn-primary" type="submit" name="submit" id="submit" value="<?php echo t('Save'); ?>" />
          <?php echo anchor('admin/citations/', t('cancel'));?> </div>
      </div>
      <!--end span8-->
      
      <div class="col-md-4" id="similar-citations" style="padding-left:10px;">
        <div class="suggestions">...</div>
      </div>
    </div>
    <!--end-tab-edit-->
    
    <div class="tab-pane" id="tab-attach-studies" >
      <div class="field col-md-8" >
        <div id="related-surveys" ><?php echo $survey_list; ?></div>
      </div>
      <div class="col-md-4 sidebar-attach-studies" >
        <h4>Find surveys</h4>
        <div class="form-group">
          <div class="col-md-8">
            <input class="form-control" autocomplete="off" type="text" id="sk" placeholder="Search for surveys by title, country and year"/>
          </div>
          <div class="col-md-4">
            <input class="btn btn-primary" type="button" name="Find" value="Find" id="find_surveys"/>
          </div>
        </div>
        <div class="modal-survey-list"> </div>
      </div>
    </div>
  </div>
  <?php echo form_close();?> 
  </div>

<script>
    function search(force) {
        //var form_data = $("form :input").serialize();
        var form_data=$(".form #title, .form #citation-fieldset-author :input, .form #citation-fieldset-editor :input, .form #citation-fieldset-translator :input, .form #subtitle, .form #alt_title ").serialize();

        if ($.data(document.body,"form_data")==form_data) {
            return false;
        }

        //save the last search query
        $.data(document.body,"form_data",form_data);

        if (!force && form_data.length < 30) return false;

        //post form
        var find_duplicates=$.post( "<?php echo site_url('admin/citations/find_duplicates')?>", form_data );

        $(".suggestions").html("Searching, please wait...");

        find_duplicates.done(function( data ) {
            $('.suggestions').html(data);
            $('.suggestions').show();

            //highlight matching keywords
            highlight_matching_keywords();
        });
    }
    function search_related_surveys(force) {

        var sid_arr=$.data(document.body,"attached_surveys");
        var search_keywords=$("#sk").val();
        var form_data ={q: search_keywords }; //exclude: sid_arr.join(",")


        if ($.data(document.body,"search_related_survey_query")==search_keywords) {
            return false;
        }

        //save the last search query
        $.data(document.body,"search_related_survey_query",search_keywords);


        //post form
        var find_surveys=$.post( "<?php echo site_url('admin/citations/find_surveys')?>", form_data );

        $(".modal-survey-list").html("Searching, please wait...");

        find_surveys.done(function( data ) {
            $('.modal-survey-list').html(data);

            //mark the studies already attached CHECKED
            var sid_arr=$.data(document.body,"attached_surveys");

            $('.modal-survey-list .chk').each(function(){
                if ( jQuery.inArray($(this).val(),sid_arr) >-1)
                {
                    $(this).prop("checked",true);
                }
            });

        });
    }

    function refresh_attached_surveys()
    {
        var sid_arr=$.data(document.body,"attached_surveys");

        //prepare for post
        var form_data={sid: sid_arr.join(",")};

        var attached_surveys=$.post( "<?php echo site_url('admin/citations/get_formatted_surveys')?>", form_data );

        //update the attached survey list
        attached_surveys.done(function( data ) {
            $('#related-surveys').html(data);
            $(".citation-count").html(sid_arr.length);
        });

    }
</script>
<?php
/**
 *
 * Create multi-textbox field for authors, editors, translators
 *
 *	@name	name for the field (author, editor, translator)
 */
function form_author_field($name,$title)
{
    //names
    $fname=$name.'_fname';
    $lname=$name.'_lname';
    $initial=$name.'_initial';

    //read postback values
    $fnames=get_form_value($fname,isset($$fname) ? $$fname: array('') );
    $lnames=get_form_value($lname,isset($$lname) ? $$lname: array('') );
    $initials=get_form_value($initial,isset($$initial) ? $$initial: array('') );

    $table_id='citation-fieldset-'.$name;

    $output=	'<fieldset class="form-group always-visible">';
    $output.=	'<legend>'.$title.'</legend>';

    $output.=	'<table border="0" class="inline-fields field tr-spacing citation-authors-table" id="'.$table_id.'">';
    $output.=	'<tr>
					<th><label>'.t('first_name').'</label></th>
					<th><label>'.t('last_name').'</label></th>
					<th><label>'.t('middle_initial').'</label></th>
				</tr>';

    //create input fields
    for($i=0;$i<count($fnames);$i++)
    {
        $id="";
        $class=' class="dynamic"';
        $remove_link='<a href="#" onclick="remove_author_row(this);return false;">remove</a>';

        if ($i==0)
        {
            $id=sprintf('id="citation-%s-%s"',$name,$i);
            $class=' class="static"';
            $remove_link='&nbsp;';
        }

        $output.='<tr '.$id.$class.'>
					<td width="25%"><input name="'.$fname.'[]" type="text" class="author-field author-fname"  value="'.$fnames[$i].'"/></td>
					<td width="25%"><input name="'.$lname.'[]" type="text" class="author-field author-lname"  value="'.$lnames[$i].'"/></td>
					<td width="25%"><input name="'.$initial.'[]" type="text" class="author-field author-initial"  value="'.$initials[$i].'" maxlength="1"/></td>
					<td class="remove-link">'.$remove_link.'</td>
        		</tr>';
    }

    $output.=	'</table>';
    $output.=	sprintf('<a href="#" onclick="add_author_row(\'%s\',\'%s\');return false;">Click here to add more...</a>',$table_id,$name);
    $output.=	'</fieldset>';

    return $output;
}
?>

<script>
  $(document).ready(function() {
    $('.field-expanded > legend').click(function(e) {
        e.preventDefault();
        $(this).parent('fieldset').toggleClass("field-collapsed");
        return false;
    });

    $('.field-expanded > legend').parent('fieldset').toggleClass('field-collapsed');

    //change citation type
    $("#citation_type").change(function(){
        $("#change_type").click();
    });

    //delete citation attachment
    $('.delete-citation-attachment').on('click',function(e) {
        e.preventDefault();
        var url=$(this).attr("href");
        $.get(url, function() {
            //success
            $(".attachment-link").remove();
        })
        return false;
    });
});

//add a new author/translator/editor row
function add_author_row(id,name)
{
    html='<tr>';
    html+='<td width="10%"><input name="'+name+'_fname[]" class="author-field" type="text"></td>';
    html+='<td width="10%"><input name="'+name+'_lname[]" class="author-field" type="text"></td>';
    html+='<td width="10%"><input name="'+name+'_initial[]" class="author-field" type="text" maxlength="1"></td>';
    html+='<td class="remove-link"><a class="btn btn-xs btn-default" href="#" onclick="remove_author_row(this);return false;"><?php echo t('remove');?></a></td>';
    html+='</tr>';

    $("#"+id).append(html);
}

function remove_author_row(el)
{
    $(el).parent().parent().remove();
}

    //key press event handlers for form input to find matching citations
$('.form').on('keyup',':input',function(e) {
    clearTimeout($.data(this, 'timer'));
    if (e.keyCode == 13)
    {
        search(true);
    }
    else{
        $(this).data('timer', setTimeout(search, 500));
    }
});


//disable enter key on the form
$(document).on('keypress','.form input',function(e) {
    if (e.keyCode == 13)
    {
        e.stopPropagation();
        e.preventDefault();
        return false;
    }
});


//find surveys to attach to citation
$(document).on('keyup','#sk',function(e) {
    clearTimeout($.data(window, 'timer'));
    if (e.keyCode == 13){
        search_related_surveys(true);
    }
    else{
        $(window).data('timer', setTimeout(search_related_surveys, 500));
    }
});


$(document).ready(function() {
    init_attached_surveys();
    search();
});

function init_attached_surveys()
{
    //create an array of attached surveys
    var sid_arr=[];
    $( "#related-surveys .chk-sid:checked" ).each(function( i ) {
        sid_arr.push( $(this).val() );
    });

    $.data(document.body,"attached_surveys",sid_arr);
    update_attached_studies_count(sid_arr.length);
}

//update the attached studies tab color and show count
function update_attached_studies_count(survey_count)
{
    $(".citation-count").html(survey_count);

    if (survey_count<1){
        $(".citation-count").addClass("no-citations");
    }
    else{
        $(".citation-count").removeClass("no-citations");
    }
}

$("#related-surveys").on('click','.chk',function(e) {
    init_attached_surveys();
});

//add related surveys to the page
$('.modal-survey-list').on('click','.chk',function(e) {
    var sid_arr=$.data(document.body,"attached_surveys");
    if ($(this).is(":checked")){
        sid_arr.push ( $(this).val());
    }
    else{
        //remove from the list
        sid_arr=_.without(sid_arr,$(this).val());
    }

    //make the selected list unique
    sid_arr=$.unique(sid_arr);

    //update
    $.data(document.body,"attached_surveys",sid_arr);

    clearTimeout($.data(window, 'timer'));
    $(window).data('timer', setTimeout(refresh_attached_surveys, 600));
    update_attached_studies_count(sid_arr.length);
});




$(document).on('click','#open-url',function(e) {
    var url=$("#url").val();
    if(url==''){return false;}

    window.open(url);
    return false;
});



$(document).on('click','#find_surveys',function(e) {
    search_related_surveys(true);
});

function highlight_matching_keywords()
{
    var words=[];

    $(".author-fname, .author-lname, #title, #subtitle").each(function(){
        words.push( $(this).val());
    });

    words=words.join(" ");
    var keywords = words.split(' ');

    for(var i = 0; i < keywords.length; i++) {
        //$(document.body).highlight($.trim(keywords[i]));
        $(".suggestions").highlight($.trim(keywords[i]), { wordsOnly: true });
    }
}
</script>
