import { getPlatform } from './maxWebApp';

/** что доступно на платформе, в вебе нет DeviceStorage, SecureStorage, haptic, share и биометрии */
export function capabilities() {
    const platform = getPlatform();
    const mobile = platform === 'ios' || platform === 'android';
    return {
        platform,
        mobile,
        nativeBackButton: mobile,
        share: mobile,
        haptic: mobile,
        deviceStorage: mobile,
        camera: true, // input type=file с capture работает везде
    } as const;
}
