import Hls from 'hls.js';
import {
    FastForward,
    Music2,
    Pause,
    Play,
    Rewind,
    Volume2,
    VolumeX,
} from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import AudioWaveform from '@/components/audio-waveform';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Skeleton } from '@/components/ui/skeleton';
import { Slider } from '@/components/ui/slider';
import { cn } from '@/lib/utils';
import type { UploadListItem, WaveformData } from '@/types/upload';

type AudioPlayerProps = {
    upload: UploadListItem;
    variant?: 'default' | 'bar';
};

export const AUDIO_PLAYER_BAR_HEIGHT = '8.5rem';

const PLAYBACK_RATES = [0.5, 0.75, 1, 1.25, 1.5, 2] as const;
const VOLUME_STORAGE_KEY = 'audio-player:volume';
const SKIP_SECONDS = 10;

function formatMetadataSummary(upload: UploadListItem): string {
    const parts: string[] = [];
    const metadata = upload.metadata;

    if (metadata?.duration) {
        parts.push(metadata.duration);
    }

    if (metadata?.codec) {
        parts.push(metadata.codec.toUpperCase());
    }

    if (metadata?.bitrate) {
        parts.push(`${Math.round(metadata.bitrate / 1000)} kbps`);
    }

    if (metadata?.sample_rate) {
        parts.push(`${Math.round(metadata.sample_rate / 1000)} kHz`);
    }

    return parts.join(' · ');
}

function formatTime(seconds: number): string {
    if (!Number.isFinite(seconds) || seconds < 0) {
        return '0:00';
    }

    const totalSeconds = Math.floor(seconds);
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const secs = totalSeconds % 60;

    if (hours > 0) {
        return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }

    return `${minutes}:${secs.toString().padStart(2, '0')}`;
}

function readStoredVolume(): number {
    if (typeof window === 'undefined') {
        return 1;
    }

    const stored = window.localStorage.getItem(VOLUME_STORAGE_KEY);

    if (stored === null) {
        return 1;
    }

    const parsed = Number.parseFloat(stored);

    if (!Number.isFinite(parsed)) {
        return 1;
    }

    return Math.min(1, Math.max(0, parsed));
}

function WaveformSkeleton({ compact = false }: { compact?: boolean }) {
    return (
        <div
            className={cn(
                'flex items-end gap-0.5 overflow-hidden',
                compact ? 'h-14' : 'h-28 rounded-lg md:h-32',
            )}
        >
            {Array.from({ length: compact ? 48 : 80 }).map((_, index) => (
                <Skeleton
                    key={index}
                    className="min-w-0 flex-1 rounded-sm"
                    style={{
                        height: `${20 + ((index * 17) % 60)}%`,
                    }}
                />
            ))}
        </div>
    );
}

export default function AudioPlayer({
    upload,
    variant = 'default',
}: AudioPlayerProps) {
    const isBar = variant === 'bar';
    const audioRef = useRef<HTMLAudioElement>(null);
    const isScrubbingRef = useRef(false);
    const playerRef = useRef<HTMLDivElement>(null);

    const [waveform, setWaveform] = useState<WaveformData | null>(null);
    const [waveformState, setWaveformState] = useState<
        'loading' | 'loaded' | 'error'
    >('loading');
    const [isPlaying, setIsPlaying] = useState(false);
    const [currentTime, setCurrentTime] = useState(0);
    const [duration, setDuration] = useState(0);
    const [volume, setVolume] = useState(readStoredVolume);
    const [isMuted, setIsMuted] = useState(false);
    const [playbackRate, setPlaybackRate] = useState(1);

    const displayTitle = upload.metadata?.title ?? upload.original_name;
    const displayArtist = upload.metadata?.artist;

    useEffect(() => {
        const audio = audioRef.current;

        if (!audio) {
            return;
        }

        audio.pause();
        audio.removeAttribute('src');
        audio.load();

        if (!upload.hls_playlist_url) {
            return;
        }

        if (Hls.isSupported()) {
            const hls = new Hls();

            hls.loadSource(upload.hls_playlist_url);
            hls.attachMedia(audio);

            return () => {
                hls.destroy();
            };
        }

        if (audio.canPlayType('application/vnd.apple.mpegurl')) {
            audio.src = upload.hls_playlist_url;
        }
    }, [upload.hls_playlist_url, upload.uuid]);

    useEffect(() => {
        const audio = audioRef.current;

        if (!audio) {
            return;
        }

        audio.volume = volume;
        audio.muted = isMuted;
        audio.playbackRate = playbackRate;
    }, [volume, isMuted, playbackRate]);

    useEffect(() => {
        window.localStorage.setItem(VOLUME_STORAGE_KEY, volume.toString());
    }, [volume]);

    useEffect(() => {
        const audio = audioRef.current;

        if (!audio) {
            return;
        }

        const onTimeUpdate = () => {
            if (!isScrubbingRef.current) {
                setCurrentTime(audio.currentTime);
            }
        };

        const onDurationChange = () => {
            if (Number.isFinite(audio.duration)) {
                setDuration(audio.duration);
            }
        };

        const onPlay = () => setIsPlaying(true);
        const onPause = () => setIsPlaying(false);
        const onEnded = () => setIsPlaying(false);

        audio.addEventListener('timeupdate', onTimeUpdate);
        audio.addEventListener('loadedmetadata', onDurationChange);
        audio.addEventListener('durationchange', onDurationChange);
        audio.addEventListener('play', onPlay);
        audio.addEventListener('pause', onPause);
        audio.addEventListener('ended', onEnded);

        return () => {
            audio.removeEventListener('timeupdate', onTimeUpdate);
            audio.removeEventListener('loadedmetadata', onDurationChange);
            audio.removeEventListener('durationchange', onDurationChange);
            audio.removeEventListener('play', onPlay);
            audio.removeEventListener('pause', onPause);
            audio.removeEventListener('ended', onEnded);
        };
    }, [upload.uuid]);

    useEffect(() => {
        let cancelled = false;

        fetch(upload.waveform_url, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
            },
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('Failed to load waveform.');
                }

                return response.json() as Promise<WaveformData>;
            })
            .then((data) => {
                if (cancelled) {
                    return;
                }

                setWaveform(data);
                setWaveformState('loaded');
            })
            .catch(() => {
                if (cancelled) {
                    return;
                }

                setWaveform(null);
                setWaveformState('error');
            });

        return () => {
            cancelled = true;
        };
    }, [upload.uuid, upload.waveform_url]);

    const togglePlay = useCallback(async () => {
        const audio = audioRef.current;

        if (!audio) {
            return;
        }

        if (audio.paused) {
            await audio.play();
        } else {
            audio.pause();
        }
    }, []);

    const skip = useCallback(
        (delta: number) => {
            const audio = audioRef.current;

            if (!audio || duration <= 0) {
                return;
            }

            const nextTime = Math.min(
                duration,
                Math.max(0, audio.currentTime + delta),
            );

            audio.currentTime = nextTime;
            setCurrentTime(nextTime);
        },
        [duration],
    );

    const handleSeek = useCallback((time: number) => {
        const audio = audioRef.current;

        if (!audio) {
            return;
        }

        audio.currentTime = time;
        setCurrentTime(time);
    }, []);

    const handleScrubStart = useCallback(() => {
        isScrubbingRef.current = true;
    }, []);

    const handleScrubEnd = useCallback(() => {
        isScrubbingRef.current = false;
        const audio = audioRef.current;

        if (audio) {
            setCurrentTime(audio.currentTime);
        }
    }, []);

    const toggleMute = useCallback(() => {
        setIsMuted((previous) => !previous);
    }, []);

    const handleVolumeChange = useCallback((values: number[]) => {
        const nextVolume = values[0] ?? 0;

        setVolume(nextVolume);

        if (nextVolume > 0) {
            setIsMuted(false);
        }
    }, []);

    const handlePlaybackRateChange = useCallback((rate: number) => {
        setPlaybackRate(rate);
    }, []);

    const handleKeyDown = useCallback(
        (event: React.KeyboardEvent<HTMLDivElement>) => {
            if (event.code !== 'Space') {
                return;
            }

            event.preventDefault();
            void togglePlay();
        },
        [togglePlay],
    );

    const transportControls = (
        <div
            className={cn(
                'flex items-center',
                isBar ? 'justify-center gap-1.5' : 'gap-1',
            )}
        >
            <Button
                type="button"
                variant="ghost"
                size="icon"
                className={isBar ? 'size-8' : undefined}
                aria-label={`Rewind ${SKIP_SECONDS} seconds`}
                onClick={() => skip(-SKIP_SECONDS)}
            >
                <Rewind className={isBar ? 'size-4' : undefined} />
            </Button>

            <Button
                type="button"
                variant="default"
                size="icon"
                className={cn(
                    'rounded-full',
                    isBar ? 'size-9' : 'size-10',
                )}
                aria-label={isPlaying ? 'Pause' : 'Play'}
                onClick={() => void togglePlay()}
            >
                {isPlaying ? (
                    <Pause className={isBar ? 'size-4' : undefined} />
                ) : (
                    <Play className={isBar ? 'size-4' : undefined} />
                )}
            </Button>

            <Button
                type="button"
                variant="ghost"
                size="icon"
                className={isBar ? 'size-8' : undefined}
                aria-label={`Fast forward ${SKIP_SECONDS} seconds`}
                onClick={() => skip(SKIP_SECONDS)}
            >
                <FastForward className={isBar ? 'size-4' : undefined} />
            </Button>
        </div>
    );

    const volumeControls = (
        <div
            className={cn(
                'flex items-center',
                isBar ? 'gap-2' : 'gap-1.5',
            )}
        >
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className={cn(
                            'h-8 px-2 text-xs',
                            isBar && 'hidden sm:inline-flex',
                        )}
                    >
                        {playbackRate === 1 ? '1x' : `${playbackRate}x`}
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    {PLAYBACK_RATES.map((rate) => (
                        <DropdownMenuItem
                            key={rate}
                            onClick={() => handlePlaybackRateChange(rate)}
                        >
                            {rate === 1 ? 'Normal' : `${rate}x`}
                        </DropdownMenuItem>
                    ))}
                </DropdownMenuContent>
            </DropdownMenu>

            <Button
                type="button"
                variant="ghost"
                size="icon"
                className={isBar ? 'size-8' : undefined}
                aria-label={isMuted ? 'Unmute' : 'Mute'}
                onClick={toggleMute}
            >
                {isMuted || volume === 0 ? (
                    <VolumeX className={isBar ? 'size-4' : undefined} />
                ) : (
                    <Volume2 className={isBar ? 'size-4' : undefined} />
                )}
            </Button>

            <Slider
                className={cn(isBar ? 'hidden w-24 md:flex' : 'w-24')}
                min={0}
                max={1}
                step={0.01}
                value={[isMuted ? 0 : volume]}
                onValueChange={handleVolumeChange}
                aria-label="Volume"
            />
        </div>
    );

    const waveformSection =
        waveformState === 'loading' ? (
            <WaveformSkeleton compact={isBar} />
        ) : waveformState === 'loaded' && waveform ? (
            <AudioWaveform
                waveform={waveform}
                duration={duration}
                currentTime={currentTime}
                onSeek={handleSeek}
                onScrubStart={handleScrubStart}
                onScrubEnd={handleScrubEnd}
                className={cn('w-full', isBar ? 'h-14' : 'h-28 md:h-32')}
            />
        ) : null;

    if (isBar) {
        return (
            <div
                ref={playerRef}
                tabIndex={0}
                onKeyDown={handleKeyDown}
                style={{ height: AUDIO_PLAYER_BAR_HEIGHT }}
                className={cn(
                    'border-border bg-background/95 supports-[backdrop-filter]:bg-background/80 flex shrink-0 flex-col border-t backdrop-blur outline-none focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                )}
            >
                <audio ref={audioRef} className="sr-only" preload="metadata" />

                <div className="h-14 shrink-0">
                    {waveformSection}
                    {waveformState === 'error' && (
                        <div className="bg-muted/50 flex h-full items-center px-4 md:px-6">
                            <p className="text-muted-foreground truncate text-xs">
                                Waveform unavailable — playback controls still
                                work.
                            </p>
                        </div>
                    )}
                </div>

                <div className="grid min-h-0 flex-1 grid-cols-[minmax(0,1fr)_auto] items-center gap-x-4 px-4 py-2 sm:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] sm:px-6">
                    <div className="flex min-w-0 items-center gap-3">
                        <div className="bg-muted flex size-9 shrink-0 items-center justify-center rounded-md">
                            <Music2 className="text-muted-foreground size-4" />
                        </div>
                        <div className="min-w-0 space-y-0.5">
                            <p className="truncate text-sm leading-tight font-medium">
                                {displayTitle}
                            </p>
                            <p className="text-muted-foreground truncate text-xs leading-tight">
                                {displayArtist ?? formatMetadataSummary(upload)}
                            </p>
                        </div>
                    </div>

                    <div className="flex flex-col items-center gap-1">
                        {transportControls}
                        <p className="text-muted-foreground text-xs tabular-nums">
                            {formatTime(currentTime)} / {formatTime(duration)}
                        </p>
                    </div>

                    <div className="hidden justify-end sm:flex">
                        {volumeControls}
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div
            ref={playerRef}
            tabIndex={0}
            onKeyDown={handleKeyDown}
            className={cn(
                'flex flex-col gap-4 rounded-lg outline-none focus-visible:ring-ring/50 focus-visible:ring-[3px]',
            )}
        >
            <div>
                <h2 className="text-lg font-semibold">{displayTitle}</h2>
                {displayArtist && (
                    <p className="text-muted-foreground text-sm">
                        {displayArtist}
                    </p>
                )}
                {formatMetadataSummary(upload) && (
                    <p className="text-muted-foreground mt-1 text-xs">
                        {formatMetadataSummary(upload)}
                    </p>
                )}
            </div>

            <audio ref={audioRef} className="sr-only" preload="metadata" />

            {waveformState === 'loading' && <WaveformSkeleton />}

            {waveformState === 'error' && (
                <Alert variant="destructive">
                    <AlertTitle>Waveform unavailable</AlertTitle>
                    <AlertDescription>
                        Could not load the waveform for this audio. Playback
                        controls still work.
                    </AlertDescription>
                </Alert>
            )}

            {waveformState === 'loaded' && waveform && (
                <div className="overflow-hidden rounded-lg">
                    {waveformSection}
                </div>
            )}

            <div className="flex flex-wrap items-center gap-3">
                {transportControls}

                <p className="text-muted-foreground min-w-28 text-sm tabular-nums">
                    {formatTime(currentTime)} / {formatTime(duration)}
                </p>

                <div className="ml-auto flex items-center gap-2">
                    {volumeControls}
                </div>
            </div>
        </div>
    );
}
