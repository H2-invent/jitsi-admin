import React from 'react';
import { DashboardConfigContext } from '../DashboardPage';
import { labelWithNumber } from '../utils/rooms';
import type { Room } from '../types';

export interface FavoriteRoomCardProps {
    room: Room;
    isRunning: boolean;
    open: boolean;
    closed: boolean;
    occupantNames: string[];
    pending: boolean;
}

const FavoriteRoomCard = React.memo(function FavoriteRoomCard({ room, isRunning, open, closed, occupantNames, pending }: FavoriteRoomCardProps) {
    const { config, onToggleFavorite } = React.useContext(DashboardConfigContext)!;
    const tr = config.translations;
    const useMultiframe = config.useMultiframe;

    const handleStar = (e: React.MouseEvent<HTMLAnchorElement>) => {
        e.preventDefault();
        if (!pending) {
            onToggleFavorite(room);
        }
    };

    let info: React.ReactNode = null;
    if (!open) {
        let text: string | null = null;
        if (!room.isPersistent && !room.isSchedule) {
            text = room.start ? room.start.dateTime : null;
        } else if (room.isSchedule) {
            text = tr.schedulePlanning;
        } else if (room.isPersistent) {
            text = tr.fixedRoom;
        }
        info = (
            <p>
                <small>{text}</small>
            </p>
        );
    } else {
        info = (
            <div className="occupant">
                <div className="number">
                    <small>{labelWithNumber(tr.inConferenceNumber, occupantNames.length)}</small>
                </div>
                <div className="text">
                    <small>{tr.inConference}</small>
                </div>
            </div>
        );
    }

    const startButton = (
        <a
            className={`btn btn-outline-primary dropdown-toggle btn-sm ${useMultiframe ? 'startIframe' : ''}`}
            data-roomname={room.name}
            href={room.joinUrl}
            target="_blank"
            rel="opener"
        >
            <i className="fas fa-play me-1" /> {tr.start}
        </a>
    );

    let scheduleButton: React.ReactNode = null;
    if (room.isSchedule) {
        if (room.canOrganize && room.scheduleAdminUrl) {
            scheduleButton = (
                <a className="loadContent btn btn-outline-primary dropdown-toggle btn-sm" href={room.scheduleAdminUrl}>
                    <i className="fa fa-calendar" />
                </a>
            );
        } else {
            scheduleButton = (
                <a
                    className="btn btn-outline-primary dropdown-toggle btn-sm"
                    href={room.schedulePublicUrl || undefined}
                    target="_blank"
                >
                    <i className="fa fa-calendar" />
                </a>
            );
        }
    }

    return (
        <div className={`card favorites ${isRunning ? 'successBorder' : ''}`}>
            <div className="card-background" />
            <div className="card-body d-flex flex-column justify-content-between">
                <h6 className="card-title favoriteTitle">
                    {room.name}
                    <small>
                        <a href={room.favoriteUrl} onClick={handleStar}>
                            {pending ? (
                                <i className="fas fa-spinner fa-spin" />
                            ) : (
                                <i className="fa fa-star" />
                            )}
                        </a>
                    </small>
                </h6>
                {info}
                <div className={room.isSchedule ? 'test' : 'startConferenceFromFavorite'}>
                    {room.isSchedule ? scheduleButton : startButton}
                </div>
            </div>
        </div>
    );
});

export default FavoriteRoomCard;
