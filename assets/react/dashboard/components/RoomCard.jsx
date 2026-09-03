import React, { memo, useContext, useEffect, useRef } from 'react';
import { DashboardConfigContext } from '../DashboardPage';
import OptionDropdown from './OptionDropdown';
import { initMdbComponents } from '../utils/mdb';
import { labelWithTime } from '../utils/rooms';
import { NameColumn, ReadonlyColumn, StatusColumn } from './RoomCardShared';

function StartButton({ room, tr, config }) {
    const useMultiframe = config.useMultiframe;
    return (
        <div className={useMultiframe ? 'start-iframe' : ''}>
            <a
                className={`btn btn-primary ${useMultiframe ? 'startIframe' : ''}`}
                data-roomname={room.name}
                data-iframetoast={room.actions.start.iframeToast}
                href={room.actions.start.url}
                rel="opener"
                target="_blank"
            >
                <i className="fas fa-play me-1" /> {tr.start}
            </a>
        </div>
    );
}

function RoomActions({ room, tr }) {
    const { config } = useContext(DashboardConfigContext);
    const actions = room.actions;
    if (room.canOrganize) {
        return (
            <div className="col-md-6 hide">
                <OptionDropdown items={actions.optionItems} translationKey="options" />
                {actions.participantsManage && (
                    <a
                        className="element btn btn-outline-primary loadContent moderator-participants"
                        href={actions.participantsManage.href}
                    >
                        <i className="fa-solid fa-users" />
                    </a>
                )}
                {actions.shareLink && (
                    <a
                        className="element btn btn-outline-primary loadContent moderator-sharelink"
                        href={actions.shareLink.href}
                    >
                        <i className="fa fa-link" /> {tr.joinLink}
                    </a>
                )}
            </div>
        );
    }

    return (
        <div className="col-md-6 hide">
            {actions.leave && (
                <a
                    className={`element btn btn-outline-default ${(actions.leave.classes || []).join(' ')} participants-remove`}
                    href={actions.leave.href}
                    data-text={actions.leave.confirmText}
                >
                    <i className="fa-solid fa-trash" />
                </a>
            )}
            {actions.icons.length > 0 && (
                <div className="btn-group element" role="group" aria-label="Basic example">
                    {actions.icons.map((item) => (
                        <a
                            key={item.key}
                            className={`element btn btn-outline-primary ${(item.classes || []).join(' ')}`}
                            href={item.href}
                            data-roomname={item.data ? item.data.roomname : undefined}
                            data-close={item.data ? item.data.close : undefined}
                        >
                            <i className={item.icon} />
                        </a>
                    ))}
                </div>
            )}
            {room.userInRoom && actions.schedule && (
                <a
                    className="btn btn-outline-primary participant-shedule"
                    href={actions.schedule.url}
                    target={actions.schedule.target}
                    rel={actions.schedule.target === '_blank' ? 'noopener' : undefined}
                >
                    {actions.schedule.icon && <i className={`${actions.schedule.icon} me-2`} />}
                    {actions.schedule.label}
                </a>
            )}
            {room.userInRoom && actions.start && <StartButton room={room} tr={tr} config={config} />}
        </div>
    );
}

const RoomCard = memo(function RoomCard({ room, open, closed, occupantNames, isFavorite, favoritePending, running, almost, minutes }) {
    const { config } = useContext(DashboardConfigContext);
    const tr = config.translations;
    const cardRef = useRef(null);

    useEffect(() => {
        if (cardRef.current) {
            initMdbComponents(cardRef.current);
        }
    }, []);

    const classes = [
        'card',
        'card-body',
        'mb-4',
        'text-lg-start',
        'text-center',
        'triggerHide',
        'mb-3',
        almost ? 'awayBorder' : '',
        running ? 'olineBorder' : '',
    ]
        .filter(Boolean)
        .join(' ');

    return (
        <div
            ref={cardRef}
            className={classes}
            id={`room_card${room.uidReal || ''}`}
            data-room-id={room.id}
        >
            <div className="row align-items-start g-4">
                {room.readOnly ? (
                    <ReadonlyColumn room={room} open={open} closed={closed} occupantNames={occupantNames} />
                ) : (
                    <>
                        <StatusColumn room={room} open={open} closed={closed} occupantNames={occupantNames} />
                        <NameColumn room={room} isFavorite={isFavorite} favoritePending={favoritePending} />
                        <RoomActions room={room} tr={tr} />
                    </>
                )}
            </div>
            {almost && <div className="showTime bg-away">{labelWithTime(tr.startingInMinutes, minutes)}</div>}
            {running && <div className="showTime bg-online">{tr.now}</div>}
        </div>
    );
});

export default RoomCard;
