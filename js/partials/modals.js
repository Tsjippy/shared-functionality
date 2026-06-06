export function createModal(id, title, content = "") {
  let modal = document.createElement("div");
  modal.classList.add("modal", "hidden", "alert");
  modal.style.zIndex = "999999999 !important";
  modal.id = id + "-modal";

  let modalContent = document.createElement("div");
  modalContent.classList.add("modal-content");

  let closeButton = document.createElement("span");
  closeButton.classList.add("close", "mobile-sticky");
  closeButton.innerHTML = "&times;";
  modalContent.appendChild(closeButton);

  let titleEl = document.createElement("h3");
  titleEl.classList.add("alert-title");
  titleEl.innerHTML = title;
  modalContent.appendChild(titleEl);

  if (content != "") {
    modalContent.append(content);
  }

  modal.appendChild(modalContent);

  return document.querySelector("body").appendChild(modal);
}

export function showModal(modal) {
  if (typeof modal == "string") {
    modal = document.getElementById(modal + "-modal");
  }

  if (modal != null) {
    // Prevent main page scrolling
    document.body.style.top = `-${window.scrollY}px`;
    document.body.style.position = "fixed";
    document.body.style.width = "100vw";

    let prim = document.getElementById("primary");
    if (prim != null) {
      prim.style.zIndex = "";
    }

    modal.classList.remove("hidden");

    modal.style.display = "block";
  }
}

export function hideModals(modals = null) {
  if (modals == null) {
    modals = document.querySelectorAll(".modal:not(.hidden)");
  }

  if (modals.forEach == undefined) {
    modals = [modals];
  }

  if (modals.length != 0) {
    modals.forEach((modal) => {
      modal.classList.add("hidden");
      modal.style.removeProperty("display");

      const event = new Event("modalclosed");
      modal.dispatchEvent(event);
    });

    // Turn main page scrolling on again
    const scrollY = document.body.style.top;
    document.body.style.position = "";
    document.body.style.top = "";
    document.body.style.width = "";
    window.scrollTo(0, parseInt(scrollY || "0") * -1);

    /* 		let prim						= document.getElementById('primary');
        if(prim != null){
            prim.style.zIndex			= 1;
        } */
  }
}
