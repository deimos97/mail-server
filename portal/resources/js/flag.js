// Bandera de España ondeando en WebGL. Sin dependencias.
//
// - Rojo #AA151B y amarillo #F1BF00 en franjas 1:2:1 (art. 4 de la Constitución; colores del RD 441/1981).
// - Si existe /img/escudo-espana.svg, se dibuja en la franja amarilla, a 1/3 del ancho desde el asta.
// - Se pausa fuera de pantalla y con la pestaña oculta; con prefers-reduced-motion se dibuja quieta.
// - Si no hay WebGL, no hace nada: el fondo CSS del hero ya tiene los colores de la bandera.

const RED = '#AA151B';
const YELLOW = '#F1BF00';

const VERTEX = `
attribute vec2 a_pos;
varying vec2 v_uv;
void main() {
    v_uv = a_pos * 0.5 + 0.5;
    gl_Position = vec4(a_pos, 0.0, 1.0);
}`;

// La onda nace en el asta (x = 0) y crece hacia el vuelo; la luz sale de la pendiente de la onda.
const FRAGMENT = `
precision mediump float;
varying vec2 v_uv;
uniform sampler2D u_flag;
uniform float u_time;
uniform vec2 u_cover;     // escala para que la bandera cubra el hero sin deformarse
void main() {
    vec2 uv = (v_uv - 0.5) * u_cover + 0.5;
    float reach = smoothstep(0.0, 0.35, uv.x);
    float phase = uv.x * 7.0 - u_time * 1.6 + uv.y * 1.3;
    float wave = sin(phase) * 0.6 + sin(phase * 1.9 + 1.7) * 0.4;
    uv.y += wave * 0.028 * reach;
    uv.x += cos(phase) * 0.006 * reach;
    float slope = cos(phase) * 0.6 + cos(phase * 1.9 + 1.7) * 0.76;
    float light = 1.0 + slope * 0.13 * reach;
    vec3 color = texture2D(u_flag, clamp(uv, 0.0, 1.0)).rgb * light;
    gl_FragColor = vec4(color, 1.0);
}`;

function compile(gl, type, source) {
    const shader = gl.createShader(type);
    gl.shaderSource(shader, source);
    gl.compileShader(shader);
    if (!gl.getShaderParameter(shader, gl.COMPILE_STATUS)) {
        throw new Error(gl.getShaderInfoLog(shader));
    }
    return shader;
}

async function flagTexture() {
    const width = 1500;
    const height = 1000;
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');

    ctx.fillStyle = RED;
    ctx.fillRect(0, 0, width, height);
    ctx.fillStyle = YELLOW;
    ctx.fillRect(0, height / 4, width, height / 2);

    // Escudo opcional: centrado a 1/3 del largo desde el asta, 2/5 del alto de la bandera.
    try {
        const image = new Image();
        image.src = '/img/escudo-espana.svg';
        await image.decode();
        const h = height * 0.4;
        const w = h * (image.naturalWidth / image.naturalHeight);
        ctx.drawImage(image, width / 3 - w / 2, height / 2 - h / 2, w, h);
    } catch {
        // Sin escudo: solo franjas.
    }

    return canvas;
}

export async function mountFlag(canvas) {
    const gl = canvas.getContext('webgl', { antialias: false, alpha: false, powerPreference: 'low-power' });
    if (!gl) return;

    const program = gl.createProgram();
    gl.attachShader(program, compile(gl, gl.VERTEX_SHADER, VERTEX));
    gl.attachShader(program, compile(gl, gl.FRAGMENT_SHADER, FRAGMENT));
    gl.linkProgram(program);
    gl.useProgram(program);

    const buffer = gl.createBuffer();
    gl.bindBuffer(gl.ARRAY_BUFFER, buffer);
    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 1, -1, -1, 1, 1, 1]), gl.STATIC_DRAW);
    const position = gl.getAttribLocation(program, 'a_pos');
    gl.enableVertexAttribArray(position);
    gl.vertexAttribPointer(position, 2, gl.FLOAT, false, 0, 0);

    const texture = gl.createTexture();
    gl.bindTexture(gl.TEXTURE_2D, texture);
    gl.pixelStorei(gl.UNPACK_FLIP_Y_WEBGL, true);
    gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, gl.RGBA, gl.UNSIGNED_BYTE, await flagTexture());
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.LINEAR);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE);

    const uTime = gl.getUniformLocation(program, 'u_time');
    const uCover = gl.getUniformLocation(program, 'u_cover');

    const resize = () => {
        const dpr = Math.min(window.devicePixelRatio || 1, 1.5);
        canvas.width = Math.round(canvas.clientWidth * dpr);
        canvas.height = Math.round(canvas.clientHeight * dpr);
        gl.viewport(0, 0, canvas.width, canvas.height);
        // "object-fit: cover" para una bandera 3:2 (con un 6 % de margen para que la onda no enseñe bordes)
        const view = canvas.width / canvas.height;
        const flag = 3 / 2;
        const cover = view > flag ? [1, flag / view] : [view / flag, 1];
        gl.uniform2f(uCover, cover[0] * 0.94, cover[1] * 0.94);
    };
    resize();

    const still = window.matchMedia('(prefers-reduced-motion: reduce)');
    let visible = true;
    let frame = null;
    const start = performance.now();

    const draw = (now) => {
        gl.uniform1f(uTime, still.matches ? 0.8 : (now - start) / 1000);
        gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
        frame = !still.matches && visible && !document.hidden ? requestAnimationFrame(draw) : null;
    };
    const play = () => { if (frame === null) frame = requestAnimationFrame(draw); };

    new ResizeObserver(() => { resize(); play(); }).observe(canvas);
    new IntersectionObserver(([entry]) => { visible = entry.isIntersecting; if (visible) play(); }).observe(canvas);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) play(); });
    still.addEventListener('change', play);

    play();
    canvas.classList.add('is-ready');
}
