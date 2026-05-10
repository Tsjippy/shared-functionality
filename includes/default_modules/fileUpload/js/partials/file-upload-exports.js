export function createProgressBar(target){
	//Show loading gif
	target.closest('.upload-div').querySelectorAll(".loader-wrapper").forEach(loader =>{
		loader.classList.remove('hidden');
		loader.querySelectorAll(".loader-text").forEach(el =>{
			el.textContent = "Preparing upload";
			el.classList.add('upload-message');
		});
	});
	
	var html	= ` 
	<div id="progress-wrapper" class="hidden">
		<progress id="upload-progress" value="0" max="100"></progress>
		<span id="progress-percentage">   0%</span>
	</div>
	`;

	fileUploadWrap.insertAdjacentHTML('beforeEnd', html);
}