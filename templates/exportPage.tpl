{**
* plugins/importexport/archivematica/templates/index.tpl
*
* List of operations this plugin can perform
*}
{extends file="layouts/backend.tpl"}

{block name="page"}

<script type="text/javascript">
	/*styles*/

	{literal}
	$('<style>').text(
			`
						.dot{
							height: 20px;
							width: 20px;
							border-radius: 50%;
							display: inline-block;
						}
						.dot-available{
							border-radius: 20%;
							display: inline-block;
							text-align: center;
							background-color: #5ccad0;
							padding-left: 5px;
							padding-right: 5px;
						}
						.dot-deposited {
							background-color: #1bbb22;
						}
						.dot-expired {
							background-color: #ca1a1a;
						}
						.dot-not-all {
							background-color: #e89d12;
						}
						.dot-not-deposited {
							background-color: #ccc;
						}
						.info-aep{
							border-top: 1px solid #ccc;
							border-bottom: 1px solid #ccc;
							width: 250px;
							height: 120px;
							float: right;
							margin-top: -2em;
						}
						.info-eap-li{
							display: flex;
						}
						`
	).appendTo(document.head);
	{/literal}

	/*styles*/


	// Attach the JS file tab handler.
	$(function() {ldelim}
	$('#exportTabs').pkpHandler('$.pkp.controllers.TabHandler');
	$('#exportTabs').tabs('option', 'cache', true);


	$(document).on("click", ".submitFormButton", function(e){
		pId = $(this).parent().parent().prop("id");
		if(pId == "issuesXmlForm"){
			var strCheck = '';
			if($(this).hasClass("submissionButton")){
				strCheck = 'selectedSubmissions';
			}else{
				strCheck = 'selectedIssues';
			}

			var checked = $('input[name=' + strCheck + '\\[\\]]').is(':checked');
			if(!checked){
				alert('{translate key="plugins.importexport.archivematica.selectCheckbox"}');
				e.preventDefault();
			}
		}

		
		if(pId == 'archivematicaSettingsForm'){
			event.preventDefault();
			var formData = {};
			formData['csrfToken'] = $('#archivematicaSettingsForm input[name="csrfToken"]').val();
			$.each($('#archivematicaSettingsForm').find('input.field'),function(index,value){
				formData[value.name] = document.querySelector('[name="'+value.name+'"]').value;
			});
			$.post("{plugin_url path="saveSettings"}",formData)
			.done(function(response){
				window.location.href="{plugin_url path=""}";
			})
			.fail(function(error){
				alert(error);
			});
		}
		
		

	});

	{rdelim});
</script>

<div id="exportTabs">
	<ul>
		<li><a href="#exportIssues-tab">{translate key="plugins.importexport.archivematica.exportIssues"}</a></li>
		<li><a href="#settings-tab">{translate key="plugins.importexport.archivematica.settings"}</a></li>
	</ul>

	<div id="exportIssues-tab">

		<script type="text/javascript">
			$(function() {ldelim}
				// Attach the form handler.
				$('#exportIssuesXmlForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
			{rdelim});
		</script>
		<form id="exportIssuesXmlForm" class="pkp_form" action="{plugin_url path="exportSubmissions"}" method="post">
			{csrf}
			{fbvFormArea id="issuesXmlForm"}

				
				{*capture assign="issuesListGridUrl"}{url router=$smarty.const.ROUTE_COMPONENT component="grid.issues.ExportableIssuesListGridHandler" op="fetchGrid" escape=false}{/capture}
				{load_url_in_div id="issuesListGridContainer" url=$issuesListGridUrl*}
				{*$smarty.server.REQUEST_URI|cat:"/getGrid/issues"*}
			{assign var="issuesListGridUrl" value=$smarty.server.REQUEST_URI|cat:"/getGrid/issues"}
			{load_url_in_div id="issuesListGridContainer" url=$issuesListGridUrl}

				<div class="info-aep">
					<ul>
						<li class="info-eap-li"><span class="dot dot-deposited"></span>&nbsp;&nbsp;{translate key="plugins.importexport.archivematica.preserved"}</li>
						<li class="info-eap-li"><span class="dot dot-not-all"></span>&nbsp;&nbsp;{translate key="plugins.importexport.archivematica.partiallyPreserved"}</li>
						<li class="info-eap-li"><span class="dot dot-not-deposited"></span>&nbsp;&nbsp;{translate key="plugins.importexport.archivematica.notPreserved"}</li>
					</ul>
				</div>

				{fbvFormButtons submitText="plugins.importexport.archivematica.exportIssues" hideCancel="true"}
			{/fbvFormArea}

		</form>
	</div>
	
	<div id="settings-tab">

		{assign var="archivematicaExportSettingsGridUrl" value={plugin_url path="loadSettings"}}
		{load_url_in_div id="archivematicaSettingsForm" url=$archivematicaExportSettingsGridUrl}

	</div>
</div>

{/block}