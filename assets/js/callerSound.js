import callerSound from '../sound/ringtone.mp3';

// The ad-hoc ringing dialog plays the caller ringtone as a fire-and-forget Audio element. Without
// keeping a reference there is no way to stop it when the callee answers or declines, so the sound
// outlives the dialog. This module owns that single Audio instance and loops it, because
// ringtone.mp3 (~13s) is usually shorter than the configured signaling timeout.
let callerRingtone = null;
let callerRingtoneDeadline = null;

// `timeoutSeconds` is the configured ADHOC_CALL_SIGNALING_DURATION. Scheduling the stop here
// guarantees the ringtone ends after that time even if the server-side (Mercure/messenger)
// stop never arrives.
export function startCallerRingtone(timeoutSeconds) {
    stopCallerRingtone();
    callerRingtone = new Audio(callerSound);
    callerRingtone.loop = true;
    callerRingtone.play().catch(() => {
        // Autoplay may be blocked until the user interacts with the page; that is fine.
    });

    const timeoutMs = Number(timeoutSeconds) * 1000;
    if (timeoutMs > 0) {
        callerRingtoneDeadline = setTimeout(stopCallerRingtone, timeoutMs);
    }
}

export function stopCallerRingtone() {
    if (callerRingtoneDeadline) {
        clearTimeout(callerRingtoneDeadline);
        callerRingtoneDeadline = null;
    }
    if (callerRingtone) {
        callerRingtone.pause();
        callerRingtone.currentTime = 0;
        callerRingtone = null;
    }
}
