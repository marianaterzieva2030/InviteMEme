const canvas = document.getElementById("inviteCanvas");
const ctx = canvas.getContext("2d");

let backgroundImage = null;
const defaultCanvasWidth = 600;
const defaultCanvasHeight = 350;
const maxPreviewSize = 600;


const imageInput = document.getElementById("image");
const nameInput = document.getElementById("name");

const typeRadios = document.querySelectorAll("input[name='type']");

function drawCanvas() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    if (backgroundImage) {
        ctx.drawImage(backgroundImage, 0, 0, canvas.width, canvas.height);
    } else {
        ctx.fillStyle = "#fff";
        ctx.fillRect(0, 0, canvas.width, canvas.height);
    }

    ctx.fillStyle = "#000";
    ctx.font = "24px Arial";

    const isMeme = document.querySelector("input[name='type']:checked")?.value === "meme";
}

function setCanvasAspect(img) {
    if (!img) {
        canvas.width = defaultCanvasWidth;
        canvas.height = defaultCanvasHeight;
        return;
    }

    let width = img.width;
    let height = img.height;

    let ratio;

    if (height > width) {

        ratio = maxPreviewSize / height;

        height = maxPreviewSize;
        width = Math.round(width * ratio);

    } else {

        ratio = maxPreviewSize / width;

        width = maxPreviewSize;
        height = Math.round(height * ratio);
    }

    canvas.width = width;
    canvas.height = height;
}

imageInput.addEventListener("change", function () {
    const file = this.files[0];
    if (!file) return;

    const img = new Image();
    const objectUrl = URL.createObjectURL(file);

    img.src = objectUrl;
    img.onload = () => {
        backgroundImage = img;
        URL.revokeObjectURL(objectUrl);
        setCanvasAspect(backgroundImage);
        drawCanvas();
    };


});

setState();
drawCanvas();