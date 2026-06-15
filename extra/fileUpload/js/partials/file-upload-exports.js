export function createProgressBar(target) {
  //Show loading gif
  var html = ` 
	<div id="progress-wrapper" class="hidden">
		<progress id="upload-progress" value="0" max="100"></progress>
		<span id="progress-percentage">   0%</span>
	</div>
	`;

  target.closest(".file-upload-wrap").insertAdjacentHTML("afterbegin", html);
}
