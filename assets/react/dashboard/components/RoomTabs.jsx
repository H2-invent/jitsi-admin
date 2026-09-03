import React from 'react';
import { DashboardConfigContext } from '../DashboardPage';
import FuturePane from './FuturePane';
import PastPane from './PastPane';
import FixedPane from './FixedPane';

export default function RoomTabs({ rooms, status, liveById, favoriteIds, favoritePending, onVisibleIdsChange }) {
    const { config } = React.useContext(DashboardConfigContext);
    const tr = config.translations;

    return (
        <>
            <div className="d-block">
                <ul className="nav-mat nav-mat-tabs mb-3" id="ex1" data-swipe="#ex1-content">
                    <li className="nav-mat-item mat-active ripple">
                        <a
                            className="nav-mat-link"
                            id="ex1-tab-1-tab"
                            href="#ex1-tabs-1"
                            role="tab"
                            aria-controls="ex1-tabs-1"
                            aria-selected="true"
                        >
                            <i className="fas fa-calendar" /> <span className="d-none d-lg-block">{tr.tabFuture}</span>
                        </a>
                    </li>
                    <li className="nav-mat-item ripple">
                        <a
                            className="nav-mat-link"
                            id="ex1-tab-3-tab"
                            href="#ex1-tabs-3"
                            role="tab"
                            aria-controls="ex1-tabs-3"
                            aria-selected="false"
                        >
                            <i className="fas fa-thumbtack" />
                            <span className="d-none d-lg-block"> {tr.tabFixed}</span>
                        </a>
                    </li>
                    <li className="nav-mat-item ripple">
                        <a
                            className="nav-mat-link"
                            id="ex1-tab-2-tab"
                            href="#ex1-tabs-2"
                            role="tab"
                            aria-controls="ex1-tabs-2"
                            aria-selected="false"
                        >
                            <i className="fas fa-history" />
                            <span className="d-none d-lg-block"> {tr.tabPast}</span>
                        </a>
                    </li>
                </ul>
            </div>
            <div className="tab-content" id="ex1-content" data-swipe="#ex1">
                <div className="tab-content-watch mat-active">
                    <div
                        className="tab-pane fade show mat-active"
                        id="ex1-tabs-1"
                        role="tabpanel"
                        aria-labelledby="ex1-tab-1"
                    >
                        <FuturePane rooms={rooms} status={status} liveById={liveById} favoriteIds={favoriteIds} favoritePending={favoritePending} onVisibleIdsChange={onVisibleIdsChange} />
                    </div>
                </div>
                <div className="tab-content-watch">
                    <div
                        className="tab-pane fade"
                        id="ex1-tabs-2"
                        role="tabpanel"
                        aria-labelledby="ex1-tab-2"
                    >
                        <PastPane rooms={rooms} status={status} favoriteIds={favoriteIds} favoritePending={favoritePending} />
                    </div>
                </div>
                <div className="tab-content-watch">
                    <div
                        className="tab-pane fade"
                        id="ex1-tabs-3"
                        role="tabpanel"
                        aria-labelledby="ex1-tab-3"
                    >
                        <FixedPane rooms={rooms} status={status} liveById={liveById} favoriteIds={favoriteIds} favoritePending={favoritePending} />
                    </div>
                </div>
            </div>
        </>
    );
}
