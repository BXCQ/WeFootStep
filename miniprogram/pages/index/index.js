// libs/miniprogram/pages/index/index.js
Page({
  data: {
    steps: 'N/A',
    lastSyncTime: '未同步',
    loading: false,
    isMock: false, // Flag to indicate if the data is mock
  },

  onLoad: function () {
    // Attempt to load last sync data from storage
    const lastSync = wx.getStorageSync('wefootstep_last_sync');
    if (lastSync) {
      this.setData({
        steps: lastSync.steps,
        lastSyncTime: lastSync.time,
        isMock: lastSync.isMock || false,
      });
    }
    // 页面加载时，在后台自动同步一次
    this.syncSteps({ background: true });
  },

  syncSteps: function (options = {}) {
    const isBackground = options.background || false;

    if (!isBackground) {
      this.setData({ loading: true });
      wx.showLoading({
        title: '正在同步...',
        mask: true
      });
    }

    wx.login({
      success: res => {
        if (res.code) {
          wx.getWeRunData({
            success: runRes => {
              // Got encrypted data, send to server
              wx.request({
                // URL format for a Typecho action: /index.php/action/[actionName]
                url: 'http(s)://your-domain.com/index.php/action/wefootstep?do=sync',
                method: 'POST',
                data: {
                  code: res.code,
                  encryptedData: runRes.encryptedData,
                  iv: runRes.iv
                },
                success: serverRes => {
                  if (!isBackground) {
                    wx.hideLoading();
                  }
                  if (serverRes.data && serverRes.data.status === 'success') {
                    const todaySteps = serverRes.data.today_steps;
                    const syncTime = this.formatTime(new Date());

                    this.setData({
                      steps: todaySteps,
                      lastSyncTime: syncTime,
                      loading: false,
                      isMock: false,
                    });

                    // Save to storage
                    wx.setStorageSync('wefootstep_last_sync', {
                      steps: todaySteps,
                      time: syncTime,
                      isMock: false
                    });

                    if (!isBackground) {
                      wx.showToast({
                        title: '同步成功',
                        icon: 'success'
                      });
                    }

                  } else {
                    // Handle server-side error
                    if (!isBackground) {
                      this.setData({ loading: false });
                      wx.showModal({
                        title: '同步失败',
                        content: serverRes.data.message || '服务器返回错误',
                        showCancel: false
                      });
                    }
                  }
                },
                fail: () => {
                  if (!isBackground) {
                    wx.hideLoading();
                    this.setData({ loading: false });
                    wx.showModal({
                      title: '同步失败',
                      content: '请求服务器失败，请检查网络',
                      showCancel: false
                    });
                  }
                }
              });
            },
            fail: () => {
              if (!isBackground) {
                wx.hideLoading();
                this.setData({ loading: false });
                wx.showModal({
                  title: '同步失败',
                  content: '获取微信运动数据失败，请确认授权',
                  showCancel: false
                });
              }
            }
          });
        } else {
          if (!isBackground) {
            wx.hideLoading();
            this.setData({ loading: false });
            wx.showModal({
              title: '同步失败',
              content: '微信登录失败',
              showCancel: false
            });
          }
        }
      },
      fail: () => {
        if (!isBackground) {
          wx.hideLoading();
          this.setData({ loading: false });
          wx.showModal({
            title: '同步失败',
            content: '无法连接到微信服务',
            showCancel: false
          });
        }
      }
    });
  },

  formatTime: function (date) {
    const year = date.getFullYear();
    const month = date.getMonth() + 1;
    const day = date.getDate();
    const hour = date.getHours();
    const minute = date.getMinutes();
    const second = date.getSeconds();

    return [year, month, day].map(this.formatNumber).join('/') + ' ' + [hour, minute, second].map(this.formatNumber).join(':');
  },

  formatNumber: function (n) {
    n = n.toString();
    return n[1] ? n : '0' + n;
  }
}); 