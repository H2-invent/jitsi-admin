import React, { memo, useEffect, useRef } from 'react';
import OptionDropdown from './OptionDropdown';
import { initMdbComponents } from '../utils/mdb';
import { StatusColumn, NameColumn, ReadonlyColumn } from './RoomCardShared';
import type { Room } from '../types';

export interface PastRoomCardProps {
    room: Room;
    open: boolean;
    closed: boolean;
    occupantNames: string[];
    isFavorite: boolean;
    favoritePending: boolean;
}

const PastRoomCard = memo(function PastRoomCard({ room, open, closed, occupantNames, isFavorite, favoritePending }: PastRoomCardProps) {
    const cardRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (cardRef.current) {
            initMdbComponents(cardRef.current);
        }
    }, []);

    return (
        <div
            ref={cardRef}
            className="card card-body mb-1 text-lg-start text-center mb-3"
            data-room-id={room.id}
        >
            <div className="row">
                {room.readOnly ? (
                    <ReadonlyColumn room={room} open={open} closed={closed} occupantNames={occupantNames} />
                ) : (
                    <>
                        <StatusColumn room={room} open={open} closed={closed} occupantNames={occupantNames} />
                        <NameColumn room={room} isFavorite={isFavorite} favoritePending={favoritePending} />
                        <div className="col-md-4 d-flex align-items-start justify-content-lg-start justify-content-center">
                            {room.canOrganize ? (
                                <>
                                    <OptionDropdown items={room.actions.optionItems} translationKey="options" />
                                    {room.pastParticipantsUrl && (
                                        <a
                                            className="element btn btn-outline-primary loadContent"
                                            href={room.pastParticipantsUrl}
                                        >
                                            <i className="fas fa-users" />
                                        </a>
                                    )}
                                </>
                            ) : (
                                room.actions.leave && (
                                    <a
                                        className="btn btn-outline-default btn-darkred confirmHref dashboardRoomDelete"
                                        href={room.actions.leave.href}
                                        data-text={room.actions.leave.confirmText || undefined}
                                    >
                                        <i className="fa-solid fa-trash" />
                                    </a>
                                )
                            )}
                        </div>
                    </>
                )}
                <div className="col-md-2">
                    <p>{room.start ? room.start.date : ''}</p>
                </div>
            </div>
        </div>
    );
});

export default PastRoomCard;
