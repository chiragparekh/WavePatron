import { useCallback, useEffect, useRef } from 'react';
import { cn } from '@/lib/utils';
import type { WaveformData } from '@/types/upload';

type AudioWaveformProps = {
    waveform: WaveformData;
    duration: number;
    currentTime: number;
    onSeek: (time: number) => void;
    onScrubStart?: () => void;
    onScrubEnd?: () => void;
    className?: string;
};

function getCanvasColors(canvas: HTMLCanvasElement): {
    playedColor: string;
    unplayedColor: string;
} {
    const styles = getComputedStyle(canvas);

    return {
        playedColor: styles.getPropertyValue('--primary').trim(),
        unplayedColor: styles.getPropertyValue('--muted-foreground').trim(),
    };
}

function drawWaveform(
    canvas: HTMLCanvasElement,
    waveform: WaveformData,
    currentTime: number,
    duration: number,
    hoverX: number | null,
): void {
    const context = canvas.getContext('2d');

    if (!context) {
        return;
    }

    const devicePixelRatio = window.devicePixelRatio || 1;
    const width = canvas.clientWidth;
    const height = canvas.clientHeight;

    canvas.width = width * devicePixelRatio;
    canvas.height = height * devicePixelRatio;
    context.scale(devicePixelRatio, devicePixelRatio);

    const { playedColor, unplayedColor } = getCanvasColors(canvas);
    const centerY = height / 2;
    const barWidth = width / waveform.data.length;
    const progressX =
        duration > 0 ? (currentTime / duration) * width : 0;

    context.clearRect(0, 0, width, height);

    for (let index = 0; index < waveform.data.length; index++) {
        const [min, max] = waveform.data[index];
        const x = index * barWidth;
        const topY = centerY - max * centerY;
        const bottomY = centerY - min * centerY;
        const barHeight = Math.max(bottomY - topY, 1);
        const barX = x;
        const barW = Math.max(barWidth - 0.5, 1);
        const isPlayed = x + barW <= progressX;

        context.globalAlpha = isPlayed ? 1 : 0.4;
        context.fillStyle = isPlayed ? playedColor : unplayedColor;

        context.beginPath();
        context.roundRect(barX, topY, barW, barHeight, 1);
        context.fill();
    }

    context.globalAlpha = 1;

    if (hoverX !== null) {
        context.strokeStyle = playedColor;
        context.globalAlpha = 0.6;
        context.lineWidth = 1;
        context.beginPath();
        context.moveTo(hoverX, 0);
        context.lineTo(hoverX, height);
        context.stroke();
        context.globalAlpha = 1;
    }

    if (duration > 0 && progressX > 0) {
        context.strokeStyle = playedColor;
        context.lineWidth = 2;
        context.beginPath();
        context.moveTo(progressX, 0);
        context.lineTo(progressX, height);
        context.stroke();
    }
}

export default function AudioWaveform({
    waveform,
    duration,
    currentTime,
    onSeek,
    onScrubStart,
    onScrubEnd,
    className,
}: AudioWaveformProps) {
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const hoverXRef = useRef<number | null>(null);
    const isScrubbingRef = useRef(false);
    const animationFrameRef = useRef<number | null>(null);

    const redraw = useCallback(() => {
        const canvas = canvasRef.current;

        if (!canvas) {
            return;
        }

        if (animationFrameRef.current !== null) {
            cancelAnimationFrame(animationFrameRef.current);
        }

        animationFrameRef.current = requestAnimationFrame(() => {
            drawWaveform(
                canvas,
                waveform,
                currentTime,
                duration,
                hoverXRef.current,
            );
            animationFrameRef.current = null;
        });
    }, [waveform, currentTime, duration]);

    useEffect(() => {
        redraw();
    }, [redraw]);

    useEffect(() => {
        const canvas = canvasRef.current;

        if (!canvas) {
            return;
        }

        const observer = new ResizeObserver(() => {
            redraw();
        });

        observer.observe(canvas);

        return () => {
            observer.disconnect();

            if (animationFrameRef.current !== null) {
                cancelAnimationFrame(animationFrameRef.current);
            }
        };
    }, [redraw]);

    const seekFromClientX = useCallback(
        (clientX: number) => {
            if (duration <= 0) {
                return;
            }

            const canvas = canvasRef.current;

            if (!canvas) {
                return;
            }

            const rect = canvas.getBoundingClientRect();
            const ratio = Math.min(
                1,
                Math.max(0, (clientX - rect.left) / rect.width),
            );

            onSeek(ratio * duration);
        },
        [duration, onSeek],
    );

    const handlePointerDown = useCallback(
        (event: React.PointerEvent<HTMLCanvasElement>) => {
            if (duration <= 0) {
                return;
            }

            isScrubbingRef.current = true;
            event.currentTarget.setPointerCapture(event.pointerId);
            onScrubStart?.();
            seekFromClientX(event.clientX);
        },
        [duration, onScrubStart, seekFromClientX],
    );

    const handlePointerMove = useCallback(
        (event: React.PointerEvent<HTMLCanvasElement>) => {
            const canvas = canvasRef.current;

            if (!canvas) {
                return;
            }

            const rect = canvas.getBoundingClientRect();
            hoverXRef.current = event.clientX - rect.left;
            redraw();

            if (isScrubbingRef.current) {
                seekFromClientX(event.clientX);
            }
        },
        [redraw, seekFromClientX],
    );

    const handlePointerUp = useCallback(
        (event: React.PointerEvent<HTMLCanvasElement>) => {
            if (!isScrubbingRef.current) {
                return;
            }

            isScrubbingRef.current = false;

            if (event.currentTarget.hasPointerCapture(event.pointerId)) {
                event.currentTarget.releasePointerCapture(event.pointerId);
            }

            onScrubEnd?.();
        },
        [onScrubEnd],
    );

    const handlePointerLeave = useCallback(() => {
        hoverXRef.current = null;
        redraw();
    }, [redraw]);

    return (
        <canvas
            ref={canvasRef}
            className={cn('cursor-pointer touch-none', className)}
            role="slider"
            aria-label="Seek audio"
            aria-valuemin={0}
            aria-valuemax={Math.round(duration)}
            aria-valuenow={Math.round(currentTime)}
            aria-valuetext={`${Math.round(currentTime)} seconds`}
            onPointerDown={handlePointerDown}
            onPointerMove={handlePointerMove}
            onPointerUp={handlePointerUp}
            onPointerCancel={handlePointerUp}
            onPointerLeave={handlePointerLeave}
        />
    );
}
