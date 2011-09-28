<?php
switch($model)
{
	case 'public':
		echo t('msg_public_data_access_type_assigned');
	break;
	
	case 'direct':
		echo t('msg_direct_data_access_type_assigned');
	break;

	case 'licensed':
		echo t('msg_licensed_data_access_type_assigned');
	break;
	
	case 'data_enclave':
		echo t('msg_enclave_data_access_type_assigned');
	break;

	case 'remote':
		//echo t('msg_remote_data_access_type_assigned');
		$this->load->view('managefiles/remote_da_form');
	break;
	
	default:
		echo t('msg_no_data_access_type_assigned');
}
?>		