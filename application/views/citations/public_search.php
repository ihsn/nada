<style>
    .badge-tiny{
        text-transform:uppercase;
        font-size:smaller;
        color:gray;
    }
    .citations-pager{        
        border:0px;
        margin-bottom:0px;
    }
    .citations-content{
        border-top:1px solid gainsboro;
        margin-top:10px;
    }
    .sort-results-by{
        border-bottom:1px solid gainsboro;
    }
</style>

<div class="tab-pane active" id="citations" role="tabpanel"  aria-expanded="true">
    <?php if (!isset($hide_form)):?>
        <?php $message=$this->session->flashdata('message');?>
        <?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

        <h3><?php echo t('citations');?></h3>
    <?php endif; ?>

    <?php if ($rows): ?>
    <?php
    //pagination
    $page_nums=$this->pagination->create_links();
    $current_page=($this->pagination->cur_page == 0) ? 1 : $this->pagination->cur_page;

    //sort
    $sort_by=$this->input->get("sort_by");
    $sort_order=$this->input->get("sort_order");

    //current page url
    $page_url=site_url().'/citations';//form_prep($this->uri->uri_string());
    ?>

    <?php
    $from_page=1;

    if ($this->pagination->cur_page>0) {
        $from_page=(($this->pagination->cur_page-1)*$this->pagination->per_page+(1));
        $to_page=$this->pagination->per_page*$this->pagination->cur_page;

        if ($to_page> $this->pagination->get_total_rows())
        {
            $to_page=$this->pagination->get_total_rows();
        }

        $pager=sprintf(t('showing %d-%d of %d')
            ,(($this->pagination->cur_page-1)*$this->pagination->per_page+(1))
            ,$to_page
            ,$this->pagination->get_total_rows());
    }
    else
    {
        $pager=sprintf(t('showing %d-%d of %d')
            ,$current_page
            ,$this->pagination->get_total_rows()
            ,$this->pagination->get_total_rows());
    }

    $persist_qfields=array('keywords','field','collection','ctype');
    ?>

    <form autocomplete="off" class="citations-listing">
        <div id="sort-results-by" class="sort-results-by nada-sort-links">
                <?php echo t('sort_results_by');?>:
                <span><?php echo create_sort_link($sort_by,$sort_order,'authors',t('authors'),$page_url,$persist_qfields ); ?></span>
                <span><?php echo create_sort_link($sort_by,$sort_order,'pub_year',t('date'),$page_url,$persist_qfields); ?></span>
                <span><?php echo create_sort_link($sort_by,$sort_order,'title',t('title'),$page_url,$persist_qfields ); ?></span>            
        </div>
        <div class="nada-pagination citations-pager">
            <div class="row mt-3 d-flex align-items-lg-center">

                <div class="col-12 col-md-6 col-lg-6 text-center text-md-left mb-2 mb-md-0">
                    <span><?php echo $pager; ?></span>
                </div>

                <div class="col-12 col-md-6 col-lg-6 d-flex justify-content-center justify-content-lg-end text-center">
                    <nav>
                        <?php echo $page_nums;?>
                    </nav>
                </div>
            </div>
        </div>

        <div class="citations-content">
        <?php $k=0;foreach($rows as $row): ?>
            <div class="citation-row nada-citation-row" data-url="<?php echo site_url('/citations/'.$row['id'].'?collection='.$active_repo);?>">
                <div class="row">
                    <!--<div class="col-1 page-num"><?php echo $from_page+$k;?></div>-->
                    <div class="col-12 row-body">
                        <div class=" badge-tiny"><?php echo t($row['ctype']);?></div>
                        <!--<h5><?php echo $row['title'];?></h5>-->

                        <?php echo $this->chicago_citation->format($row,'journal');?>                        
                    </div>
                </div>
            </div>
        <?php $k++;endforeach;?>
        </div>

        <div class="nada-pagination citations-pager">
            <div class="row mt-3 d-flex align-items-lg-center">

                <div class="col-12 col-md-3 col-lg-4 text-center text-md-left mb-2 mb-md-0">
                    <?php echo $pager; ?>
                </div>

                <div class="col-12 col-md-9 col-lg-8 d-flex justify-content-center justify-content-lg-end text-center">
                    <nav aria-label="Page navigation">
                        <?php echo $page_nums;?>
                    </nav>
                </div>
            </div>

        </div>

        <?php else: ?>
            <?php echo t('no_records_found');?>
        <?php endif; ?>
    </form>
</div>

<?php
/**
 *
 * Format authors according to chicago style
 */
function format_author($authors)
{
    $output=array();
    if (count($authors)==1)
    {
        //single author
        if ($authors[0]['lname']!='' || $authors[0]['initial'])
        {
            $tmp[]=sprintf("%s %s",trim($authors[0]['lname']), trim($authors[0]['initial']));
        }
        $tmp[]=trim($authors[0]['fname']);
        $output[]=implode(", ",$tmp);
    }
    else //multi-author
    {
        for($i=0;$i<count($authors);$i++)
        {
            if ($i==0)
            {
                if ($authors[$i]['lname']!='' || $authors[$i]['initial'])
                {
                    $tmp[]=sprintf("%s %s",trim($authors[$i]['lname']), trim($authors[$i]['initial']));
                }
                $tmp[]=trim($authors[$i]['fname']);
                $output[]=implode(", ",$tmp);
            }
            else if ($i==count($authors)-1)//last author
            {
                $output[]= sprintf('and %s %s %s', $authors[$i]['fname'],$authors[$i]['initial'],$authors[$i]['lname']);
            }
        }
    }

    $result=trim(implode(", ", $output));
    if ($result!=='')
    {
        $result.=". ";
    }
    return $result;
}

function format_place($city, $country, $publisher, $date)
{
    $output=NULL;

    if ($city!=='')
    {
        $output[]=$city;
    }
    if ($country!=='')
    {
        $output[]=$country;
    }

    //combine city and country
    $city_country=NULL;
    if ($output!==NULL)
    {
        $city_country=implode(", ", $output);
    }

    $tmp=NULL;
    //combine publisher and date
    if ($publisher!=='')
    {
        $tmp[]=$publisher;
    }
    if ($date!=='')
    {
        $tmp[]=$date;
    }

    $pub_and_date='';
    if ($tmp !=NULL)
    {
        //join publisher and date
        $pub_and_date=implode(", ", $tmp);
    }

    if ($pub_and_date!=='')
    {
        $pub_and_date.=". ";
    }

    //combine all
    $result=NULL;
    if ($city_country!=='')
    {
        $result[]=$city_country;
        $result[]=$pub_and_date;
    }

    $final_output=implode(": ", $result);

    return $final_output;
}

function format_date($day,$month,$year)
{
    $month_day='';

    //format Month, day
    if($month!='')
    {
        $month_day=$month;
    }

    if ((integer)$day>0)
    {
        if ($month!='')
        {
            $month_day.=' '. $day;
        }
        else
        {
            $month_day=$day;
        }
    }

    $output='';

    //add year
    if ((integer)$year>0)
    {

        if ($month_day!='')
        {
            $output=$month_day.', '. $year;
        }
        else
        {
            $output=$year;
        }
    }

    if ($output!='')
    {
        return $output.=".";
    }
    else
    {
        return "";
    }
}

?>
