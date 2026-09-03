export function isRunning(room, nowTs) {
    if (!room || room.start == null || room.isPersistent || room.isSchedule) {
        return false;
    }
    const start = room.start.ts;
    const end = room.end ? room.end.ts : null;
    if (start == null) {
        return false;
    }
    if (nowTs == null) {
        return false;
    }
    return start < nowTs && (end === null || end > nowTs);
}

export function almostRunning(room, nowTs) {
    if (!room || room.start == null || nowTs == null) {
        return false;
    }
    const start = room.start.ts;
    return start !== null && start - 600 < nowTs && start > nowTs;
}

export function minutesToStart(room, nowTs) {
    if (!room || room.start == null || nowTs == null) {
        return 0;
    }
    return Math.max(0, Math.round((room.start.ts - nowTs) / 60));
}

export function collectRoomIds(groups) {
    const ids = [];
    groups.forEach((group) => {
        group.rooms.forEach((room) => {
            if (room && room.id != null) {
                ids.push(room.id);
            }
        });
    });
    return ids;
}

export function labelWithNumber(pattern, number) {
    if (pattern == null) {
        return String(number);
    }
    return pattern.replace(/\{\{number\}\}/g, String(number));
}

export function labelWithTime(pattern, time) {
    if (pattern == null) {
        return String(time);
    }
    return pattern.replace(/\{\{time\}\}/g, String(time));
}
