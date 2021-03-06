<?php
/**
 * 
 * @author Krios Mane
 * @version 0.1
 * @license https://opensource.org/licenses/GPL-3.0
 * 
 */
 defined('BASEPATH') OR exit('No direct script access allowed');
 
 class Create extends FAB_Controller {
 	
	protected $runningTask = false;
	
	function __construct()
	{
		parent::__construct();
		if(!$this->input->is_cli_request()){ //avoid this form command line
			//check if there's a running task
			//load libraries, models, helpers
			$this->load->model('Tasks', 'tasks');
			//$this->tasks->truncate();
			$this->runningTask = $this->tasks->getRunning();
		}
		
		
	}
	
	//controller router
	public function index($type = 'print', $what_id = '-1'){
		
		if($this->runningTask){
			$method = 'do'.ucfirst($this->runningTask['type']);
			if(method_exists($this, $method)) $this->$method($what_id);
			else redirect('dashboard');
		}else{
			switch($type){
				case 'mill':
					$this->doMill($what_id);
					break;
				case 'print':
					$this->doPrint($what_id);
					break;
				default:
					$this->doPrint($what_id);
			}
		}
	}
	
	private function doPrint($fileId)
	{
		$this->load->library('smart');
		$this->load->helper('form');
		$this->config->load('filaments');
		$this->load->helper( array('fabtotum_helper', 'upload_helper', 'os_helper') );
		$this->load->model('Files', 'files');
		
		$data = array();
		$data['runningTask'] = $this->runningTask;
		$data['file_id'] = '';
		$data['print_type'] = 'additive';
		
		$data['head'] = getInstalledHeadInfo();
		$data['extruder_max'] = $data['head']['max_temp'];
		// Skip file selection step if fileID is provided
		$file = $this->files->get($fileId, 1);
		$file_is_ok = False;
		
		if($file)
		{
			if($file['print_type'] == 'additive')
			{
				$data['file_id'] = $fileId;
				$file_is_ok = True;
				$data['wizard_jump_to'] = 2; // jump to step 2 if fileID is available
			}
			else
			{
				$data['warning'] = _("Selected file is not for printing");
			}
		}
		
		// Skip to Job Execution step if task is already running
		$task_is_running = False;
		if($data['runningTask'])
		{
			$data['wizard_jump_to'] = 3;
			$task_is_running = True;
		}
		
		$data['type']      = 'print';
		$data['type_label'] = _("Printing");
		$data['type_action'] = _("Print");
		$data['extruder_min'] = isset($data['head']['min_temp']) ? ($data['head']['min_temp'] + 5) : 180;
		
		
		$filaments =  $this->config->item('filaments');
		$filamentsOptions = array();
		foreach($filaments as $key => $val){
		    $filamentOptions[$key] = $val['name'];
		}
		
		$data['filamentsOptions'] = $filamentOptions;
		$this->config->load('upload');
		
		$data['accepted_files'] = allowedTypesToDropzoneAcceptedFiles( $this->config->item('allowed_types') );
		
		if(!$task_is_running){
			
		    $data['settings'] = loadSettings();
			$data['safety_check'] = safetyCheck("print", "yes");
			$data['safety_check']['url'] = 'std/safetyCheck/print/yes';
			$data['safety_check']['content'] = $this->load->view( 'std/task_safety_check', $data, true );
			$data['dropzone']['content'] = $this->load->view( 'std/task_dropzone', $data, true );
			
			// select_file
			$data['get_files_url']   = 'std/getFiles/additive';
			$data['get_reacent_url'] = 'std/getRecentFiles/print';
		}
		
		// task_wizard
		$data['start_task_url'] = 'create/startPrintTask';
		$data['restart_task_url_file'] = 'make/print';
		
		$data['steps'] = array(
				array('number'  => 1,
				 'title'   => _("Choose file or drop here"),
				 'content' => !$task_is_running ? $this->load->view( 'std/select_file', $data, true ) : '',
				 'active'  => !$file_is_ok && !$task_is_running
			    ),
				array('number'  => 2,
				 'title'   => _("Get ready"),
				 'content' => !$task_is_running ? $this->load->view( 'std/print_setup', $data, true ) : '',
				 'active'  => $file_is_ok && !$task_is_running
			    ),
				array('number'  => 3,
				 'title'   => _("Printing"),
				 'content' => $this->load->view( 'std/task_execute', $data, true ),
				 'active' => $task_is_running
			    ),
				array('number'  => 4,
				 'title'   => _("Finish"),
				 'content' => $this->load->view( 'std/task_finished', $data, true )
			    )
			);
		
		$widgetOptions = array(
			'sortable'     => false, 'fullscreenbutton' => true,  'refreshbutton' => false, 'togglebutton' => false,
			'deletebutton' => false, 'editbutton'       => false, 'colorbutton'   => false, 'collapsed'    => false
		);
		
		$widget         = $this->smart->create_widget($widgetOptions);
		$widget->id     = 'main-widget-head-installation';
		$widget->header = array('icon' => 'fabui-print', "title" => "<h2>"._("Print")."</h2>");
		$widget->body   = array('content' => $this->load->view('std/task_wizard', $data, true ), 'class'=>'fuelux');

		
		$this->addCssFile('/assets/css/std/task_execute.css');

		$this->addJSFile('/assets/js/plugin/flot/jquery.flot.cust.min.js'); 
		$this->addJSFile('/assets/js/plugin/flot/jquery.flot.resize.min.js');
		$this->addJSFile('/assets/js/plugin/flot/jquery.flot.fillbetween.min.js');
		$this->addJSFile('/assets/js/plugin/flot/jquery.flot.time.min.js');
		$this->addJSFile('/assets/js/plugin/flot/jquery.flot.tooltip.min.js');
		$this->addJSFile('/assets/js/plugin/fuelux/wizard/wizard.min.old.js'); //wizard
		

		$this->addJsInLine($this->load->view( 'create/print_js', $data, true));
		$this->addJsInLine($this->load->view( 'std/task_wizard_js',   $data, true));
		
		if(!$task_is_running) { // these files aren't needed if task is already running
			$this->addCssFile('/assets/css/std/select_file.css');
			$this->addJSFile('/assets/js/plugin/datatables/jquery.dataTables.min.js'); //datatable
			$this->addJSFile('/assets/js/plugin/datatables/dataTables.colVis.min.js'); //datatable
			$this->addJSFile('/assets/js/plugin/datatables/dataTables.tableTools.min.js'); //datatable
			$this->addJSFile('/assets/js/plugin/datatables/dataTables.bootstrap.min.js'); //datatable
			$this->addJSFile('/assets/js/plugin/datatable-responsive/datatables.responsive.min.js'); //datatable */
			$this->addJSFile('/assets/js/plugin/dropzone/dropzone.js'); //dropzpone	
			$this->addJsInLine($this->load->view( 'std/task_safety_check_js', $data, true));
			$this->addJsInLine($this->load->view( 'std/task_dropzone_js', $data, true)); //important dropzone after safety check
			$this->addJsInLine($this->load->view( 'std/select_file_js',   $data, true));
			$this->addJsInLine($this->load->view( 'std/print_setup_js',   $data, true));
		}
		
		$this->addJsInLine($this->load->view( 'std/task_execute_js',  $data, true));
		$this->addJsInLine($this->load->view( 'std/task_finished_js', $data, true));

		$this->content = $widget->print_html(true);
		$this->view();
	}
	
	private function doMill($fileId)
	{
		$this->load->library('smart');
		$this->load->helper('form');
		$this->load->helper( array('fabtotum_helper', 'plugin_helper', 'upload_helper', 'os_helper') );
		$this->load->model('Files', 'files');
		
		$data = array();
		$data['runningTask'] = $this->runningTask;
		$data['file_id'] = '';
		$data['print_type'] = 'subtractive';
		
		// Skip file selection step if fileID is provided
		$file = $this->files->get($fileId, 1);
		$file_is_ok = False;
		
		if($file)
		{
			if($file['print_type'] == 'subtractive')
			{
				$data['file_id'] = $fileId;
				$file_is_ok = True;
				$data['wizard_jump_to'] = 2; // jump to step 2 if fileId is available
			}
			else
			{
				$data['warning'] = "Selected file is not for milling";
			}
		}
		
		// Skip to Job Execution step if task is already running
		$task_is_running = False;
		if($data['runningTask'])
		{
			$data['wizard_jump_to'] = 3;
			$task_is_running = True;
		}
		
		$data['type']      = 'mill';
		$data['type_label'] = _("Milling");
		$data['type_action'] = _("Mill");
		
		if(!$task_is_running){
			
			$this->config->load('upload');
			$data['accepted_files'] = allowedTypesToDropzoneAcceptedFiles( $this->config->item('allowed_types') );
			
			$data['safety_check'] = safetyCheck("mill", "no");
			$data['safety_check']['url'] = 'std/safetyCheck/mill/no';
			$data['safety_check']['content'] = $this->load->view( 'std/task_safety_check', $data, true );
			$data['dropzone']['content'] = $this->load->view( 'std/task_dropzone', $data, true );
			
			// select_file
			$data['get_files_url'] = 'std/getFiles/subtractive';
			$data['get_reacent_url'] = 'std/getRecentFiles/mill';
			
		}
		
		// task_wizard
		$data['start_task_url'] = 'create/startMillTask';
		$data['restart_task_url_file'] = 'make/mill';
		
		$data['steps'] = array(
				array('number'  => 1,
				 'title'   => _("Choose file or drope here"),
				 'content' => !$task_is_running ? $this->load->view( 'std/select_file', $data, true ) : '',
				 'active'  => !$file_is_ok && !$task_is_running
			    ),
				array('number'  => 2,
				 'title'   => _("Get ready"),
				 'content' => !$task_is_running ? $this->load->view( 'std/jog_setup', $data, true ) : '',
				 'active'  => $file_is_ok && !$task_is_running
			    ),
				array('number'  => 3,
				 'title'   => _("Milling"),
				 'content' => $this->load->view( 'std/task_execute', $data, true ),
				 'active' => $task_is_running
			    ),
				array('number'  => 4,
				 'title'   => _("Finish"),
				 'content' => $this->load->view( 'std/task_finished', $data, true )
			    )
			);
		
		$widgetOptions = array(
			'sortable'     => false, 'fullscreenbutton' => true,  'refreshbutton' => false, 'togglebutton' => false,
			'deletebutton' => false, 'editbutton'       => false, 'colorbutton'   => false, 'collapsed'    => false
		);
		
		$widget         = $this->smart->create_widget($widgetOptions);
		$widget->id     = 'main-widget-head-installation';
		$widget->header = array('icon' => 'fabui-subtractive', "title" => "<h2>"._("Mill")."</h2>");
		$widget->body   = array('content' => $this->load->view('std/task_wizard', $data, true ), 'class'=>'fuelux');

		

		$this->addJSFile('/assets/js/plugin/datatables/jquery.dataTables.min.js'); //datatable
		$this->addJSFile('/assets/js/plugin/datatables/dataTables.colVis.min.js'); //datatable
		$this->addJSFile('/assets/js/plugin/datatables/dataTables.tableTools.min.js'); //datatable
		$this->addJSFile('/assets/js/plugin/datatables/dataTables.bootstrap.min.js'); //datatable
		$this->addJSFile('/assets/js/plugin/datatable-responsive/datatables.responsive.min.js'); //datatable */
		
		$this->addJSFile('/assets/js/plugin/flot/jquery.flot.cust.min.js'); 
		$this->addJSFile('/assets/js/plugin/flot/jquery.flot.resize.min.js');
		$this->addJSFile('/assets/js/plugin/flot/jquery.flot.fillbetween.min.js');
		$this->addJSFile('/assets/js/plugin/flot/jquery.flot.time.min.js');
		$this->addJSFile('/assets/js/plugin/flot/jquery.flot.tooltip.min.js');
		
		

		$this->addJsInLine($this->load->view( 'create/mill_js', $data, true));
		
		if(!$task_is_running){
			
			$this->addCssFile('/assets/css/std/select_file.css');
			$this->addCssFile('/assets/css/std/jog_setup.css');
			$this->addCssFile('/assets/css/std/jogtouch.css');
			$this->addCssFile('/assets/css/std/jogcontrols.css');
			
			$this->addJSFile('/assets/js/std/raphael.js' ); //vector library
			$this->addJSFile('/assets/js/std/modernizr-touch.js' ); //touch device detection
			$this->addJSFile('/assets/js/std/jogcontrols.js' ); //jog controls
			$this->addJSFile('/assets/js/std/jogtouch.js' ); //jog controls
			$this->addJSFile('/assets/js/plugin/knob/jquery.knob.min.js');
			$this->addJSFile('/assets/js/plugin/dropzone/dropzone.js'); //dropzpone	
		}
		
		$this->addCssFile('/assets/css/std/task_execute.css');
		$this->addJSFile('/assets/js/plugin/fuelux/wizard/wizard.min.old.js'); //wizard
		
		
		if(!$task_is_running) { // these files aren't needed if task is already running
			$this->addJsInLine($this->load->view( 'std/task_safety_check_js', $data, true));
			$this->addJsInLine($this->load->view( 'std/task_dropzone_js', $data, true));
			$this->addJsInLine($this->load->view( 'std/select_file_js', $data, true));
			$this->addJsInLine($this->load->view( 'std/jog_setup_js', $data, true));
		}
		
		$this->addJsInLine($this->load->view( 'std/task_wizard_js', $data, true));
		$this->addJsInLine($this->load->view( 'std/task_execute_js', $data, true));
		$this->addJsInLine($this->load->view( 'std/task_finished_js', $data, true));

		$this->content = $widget->print_html(true);
		$this->view();
	}

	public function startPrintTask()
	{
		$data = $this->input->post();
		
		if(!isset($data['idFile'])){
			$this->output->set_content_type('application/json')->set_output(json_encode(array('start' => false, 'message' => 'File not selected')));
			return;
		}
		
		//load helpers
		$this->load->helpers('fabtotum_helper');
		$this->load->helpers('language_helper');
		$this->load->model('Files', 'files');
		resetTaskMonitor();
		$userID   = $this->session->user['id'];
		session_write_close(); //avoid freezing page
		
		$fileToCreate = $this->files->get($data['idFile'], 1);
		
		if($data['dropzone_file'] == 'true'){ //crate an object an assoc new file
			$this->load->model('Objects', 'objects');
			$temp['name'] = $fileToCreate['client_name'];
			$temp['description'] = 'Quick print';
			$temp['user'] = $this->session->user['id'];
			$temp['date_update'] = date('Y-m-d H:i:s');
			$temp['date_insert'] = date('Y-m-d H:i:s');
			$objectID = $this->objects->add($temp);
			$this->objects->addFiles($objectID, $data['idFile']);
		}
		
		$temperatures = readInitialTemperatures($fileToCreate['full_path']);
		if($temperatures == false){
			$this->output->set_content_type('application/json')->set_output(json_encode(array('start' => false, 'message' => 'File not found')));
			return;
		}
		
		$preparingResult = doMacro("check_additive");
		if($preparingResult['response'] != 'success'){
			$this->output->set_content_type('application/json')->set_output(json_encode(array('start' => false, 'message' => $preparingResult['message'])));
			return;
		}
		
		$preparingResult = doMacro("prepare_additive", '', [ $temperatures['extruder'], $temperatures['bed'] ]);
		if($preparingResult['response'] != 'success'){
			$this->output->set_content_type('application/json')->set_output(json_encode(array('start' => false, 'message' => $preparingResult['message'])));
			return;
		}
		
		if($data['calibration'] == 'auto_bed_leveling'){
			$calibrationResult = doMacro("auto_bed_leveling");
			if($calibrationResult['response'] != 'success'){
				$this->output->set_content_type('application/json')->set_output(json_encode(array('start' => false, 'message' => $calibrationResult['message'])));
				return;
			}
		}
		else
		{
			$calibrationResult = doMacro("home_all");
			if($calibrationResult['response'] != 'success'){
				$this->output->set_content_type('application/json')->set_output(json_encode(array('start' => false, 'message' => $calibrationResult['message'])));
				return;
			}
		}
		
		$startPrintResult = doMacro('start_additive');
		if($startPrintResult['response'] != 'success'){
			$this->output->set_content_type('application/json')->set_output(json_encode(array('start' => false, 'message' => $startPrintResult['message'], 'trace'=>$startPrintResult['trace'], 'error' => $startPrintResult['reply'])));
			return;
		}
		//get object record
		$object = $this->files->getObject($fileToCreate['id']);
		//ready to print
		//add record to DB
		$this->load->model('Tasks', 'tasks');
		$taskData = array(
			'user'       => $this->session->user['id'],
			'controller' => 'make/print',
			'type'       => 'print',
			'status'     => 'running',
			'id_file'    => $data['idFile'],
			'id_object'  => $object['id'],
			'start_date' => date('Y-m-d H:i:s') 
		);
		$taskId   = $this->tasks->add($taskData);
		
		//start print
		$printArgs = array(
			'-T' => $taskId, 
			'-F' => $fileToCreate['full_path'],
			'--lang' => getCurrentLanguage() . '.UTF-8'
		);
						
		if($data['send_email']    == "true") $printArgs['--email'] = '';
		if($data['auto_shutdown'] == "true") $printArgs['--shutdown'] = '';
		
		startPyScript('print.py', $printArgs);
		$output = array(
			'start' => true,
			'id_task' => $taskId,
			'temperatures' =>  $temperatures,
			'file' => array(
				'name' =>  $fileToCreate['client_name']
			)
		);
		$this->output->set_content_type('application/json')->set_output(json_encode($output));
	}
	
	public function startMillTask()
	{
		$data = $this->input->post();
		//load helpers
		$this->load->helpers('fabtotum_helper');
		$this->load->helpers('language_helper');
		$this->load->model('Files', 'files');
		//reset task monitor file
		resetTaskMonitor();
		$fileToCreate = $this->files->get($data['idFile'], 1);
		
		
		if($data['dropzone_file'] == 'true'){ //crate an object an assoc new file
			$this->load->model('Objects', 'objects');
			$temp['name'] = $fileToCreate['client_name'];
			$temp['description'] = 'Quick print';
			$temp['user'] = $this->session->user['id'];
			$temp['date_update'] = date('Y-m-d H:i:s');
			$temp['date_insert'] = date('Y-m-d H:i:s');
			$objectID = $this->objects->add($temp);
			$this->objects->addFiles($objectID, $data['idFile']);
		}
		
		
		$startSubtractive = doMacro('start_subtractive');
		if($startSubtractive['response'] =! 'ok'){
			$this->output->set_content_type('application/json')->set_output(json_encode(array('start' => false, 'message' => $startSubtractive['message'])));
			return;
		}
		//get object record
		$object = $this->files->getObject($fileToCreate['id']);
		//ready to print
		//add record to DB
		$this->load->model('Tasks', 'tasks');
		$taskData = array(
				'user'       => $this->session->user['id'],
				'controller' => 'make/mill',
				'type'       => 'mill',
				'status'     => 'running',
				'id_file'    => $data['idFile'],
				'id_object'  => $object['id'],
				'start_date' => date('Y-m-d H:i:s')
		);
		$taskId   = $this->tasks->add($taskData);
		
		//start milling
		$printArgs = array(
				'-T' => $taskId,
				'-F' => $fileToCreate['full_path'],
				'--lang' => getCurrentLanguage() . '.UTF-8'
		);
		startPyScript('mill.py', $printArgs);
		
		$output = array(
			'start' => true,
			'id_task' => $taskId,
			'file' => array(
				'name' =>  $fileToCreate['client_name']
			)
		);
		$this->output->set_content_type('application/json')->set_output(json_encode($output));
	}
	
	public function test()
	{
		phpinfo();
	}
}
 
?>
