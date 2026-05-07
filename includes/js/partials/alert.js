import {createModal, showModal, hideModals} from './modals.js';

export class Alert{
    /**
     * 
     * @param {*} message           The message to show
     * @param {*} type              One of success (default), warning, error, info, question or loader
     * @param {*} options           Array of extra options:
     *                                  Title
     *                                  timer                   Time in miliseconds after wich the modal will close and returns cancel
     *                                  ConfirmButtonText
     *                                  ConfirmButtonColor
     *                                  ConfirmButtonPosition
     *                                  CancelButtonText
     *                                  CancelButtonColor
     *                                  CancelButtonPosition
     *                                  CustomButtonText
     *                                  CustomButtonColor
     *                                  CustomButtonPosition
     * 
     * @returns                     A promise which fulfills when a button is clicked with a value of confirm, cancel or custom
     */
    constructor(message, type='success', options={}){
        this.message    = message;
        this.type       = type;
        this.options    = options;

        let title       = '';
        if(options.title != undefined){
            title   = options.title;
        }

        this.modal      = createModal('alert', title);

        this.addIcon();

        let content = document.createElement('div');
        content.classList.add('alert-content');

        content.append(this.message);

        this.modal.querySelector('.modal-content').append(content);

        this.addButtons();

        document.addEventListener('click', this.clicked.bind(this));

        this.show();

        return new Promise((resolve, reject) => {
            this.resolve    = resolve;
            this.reject     = reject;
        });
    }

    show(){
        showModal(this.modal);

        if(this.options.timer != undefined){
            this.timer();
        }
    }

    hide(){
        hideModals();
    }

    timer(){
        setTimeout(this.expired(), this.options.timer);
    }

    expired(){
        this.resolve('cancel');

        this.hide();
    }


    addIcon(){
        let iconWrapper = document.createElement('div');
        iconWrapper.classList.add('tsjippy-alert-icon', `tsjippy-alert-${this.type}` );

        this.modal.querySelector('.modal-content').append(iconWrapper);

        if(this.type == 'error'){
            iconWrapper.innerHTML = `
            <span class="tsjippy-alert-x-mark">
                <span class="tsjippy-alert-x-mark-line-left"></span>
                <span class="tsjippy-alert-x-mark-line-right"></span>
            </span>`;
            
        }else if(this.type == 'warning' || this.type == 'info' || this.type == 'question'){
            let icon = document.createElement('div');
            icon.classList.add("tsjippy-alert-icon-content");

            if(this.type == 'warning'){
                icon.textContent = "!";
            }else if (this.type == 'question'){
                icon.textContent = "?";
            }else {
                icon.textContent = "i";
            }

            iconWrapper.appendChild(icon);
        }else if(this.type == 'loader'){
            let loader	= Main.showLoader(iconWrapper);
        }else{
            iconWrapper.innerHTML = `
                <div class="success-circular-line-left"></div>
                <span class="success-line-tip"></span> 
                <span class="success-line-long"></span>
                <div class="success-ring"></div>
                <div class="success-fix"></div>
                <div class="success-circular-line-right"></div>
            ` ;
        }
    }

    addButtons(){
        let buttons = {};

        const types = ['Confirm', 'Cancel', 'Custom'];
                
        let position    = 0;

        types.forEach(type => {
            if(this.options[`${type}ButtonText`] != undefined){
                /**
                 * Button properties
                 */
                let text        = this.options[`${type}ButtonText`];
                let color;
                let id          = type.toLowerCase();

                /**
                 * Determine color
                 */
                if(this.options[`${type}ButtonColor`] == undefined){
                    if(id == 'confirm'){
                        color   = '#bd2919';
                    }else{
                        color   = '#8a1a0e';
                    }
                }else{
                    color   = this.options[`${type}ButtonColor`];
                }

                /**
                 * Determine position
                 */
                if(this.options[`${type}ButtonPosition`] != undefined){
                    position    = this.options[`${type}ButtonPosition`];
                }else{
                    position++;
                }

                while(buttons[position] != undefined){
                    position++;
                }

                /**
                 * Create button
                 */
                let button	= document.createElement('button');
                button.classList.add('button', 'tsjippy', 'alert');

                button.innerHTML                = text;
                button.type                     = 'button';
                button.id                       = `alert-${id}`;
                button.style.backgroundColor    = color;


                buttons[position] = button;
            }      
        });

        /**
         * Now add the buttons to the modal
         */
        let buttonWrapper	= document.createElement('div');
        buttonWrapper.classList.add('alert-button-wrapper');
        this.modal.querySelector('.modal-content').appendChild(buttonWrapper);

        Object.values(buttons).forEach( button => {
            buttonWrapper.appendChild(button);
        }); 
    }

    clicked(event){
        let target      = event.target;
        let id          = target.id;

        if(id.startsWith('alert-')){

            this.resolve(id.replace('alert-', ''));

            this.hide();
        }else if(target.matches('.close') || target.closest('#alert-modal') == null){
            this.resolve('cancel');
        }
    }
}