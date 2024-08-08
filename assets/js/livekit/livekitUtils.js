import {initSocialIcons} from "../createSocialButtons";
import {ToolbarUtils} from "../ToolbarUtils";

export class LivekitUtils {
    constructor(api) {
        this.api = api;
        this.toolbar = new ToolbarUtils();
        this.initSidebarMove();
        initSocialIcons(this.changeCamera.bind(this));
    }

    initSidebarMove() {

        this.api.iframe.addEventListener("mouseover", (event) => {
            this.toolbar.sidebarAction();
        });
        this.api.addEventListener("touchstart", (event) => {
            this.toolbar.sidebarAction();
        });
    }

    changeCamera(cameraLabel) {
        console.log(cameraLabel);
    }
}