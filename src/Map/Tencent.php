<?php

namespace Encore\Admin\Latlong\Map;

class Tencent extends AbstractMap
{
    /**
     * @var string
     */
    protected $api = '//map.qq.com/api/gljs?v=1.exp&libraries=service&key=';
    protected $sk = '';

    public function __construct($key = '')
    {
        parent::__construct($key);
        $this->api = '//map.qq.com/api/gljs?v=1.exp&libraries=service&key='. env('TENCENT_MAP_KEY');
        $this->sk = env('TENCENT_MAP_SECRET');
    }

    /**
     * {@inheritdoc}
     */
    public function applyScript(array $id)
    {
        return <<<EOT
        (function() {
            function init(name) {
            
                // 获取经纬度的输入框元素
                var lat = $('#{$id['lat']}');
                var lng = $('#{$id['lng']}');
                
                // 设置地图默认中心点坐标
                var center = new TMap.LatLng(lat.val(), lng.val());//设置中心点坐标
                
                // 初始化地图
                var map = new TMap.Map("map_"+name, {
                    center: center,
                    zoom: {$this->getParams('zoom')}
                });

                // 添加缩放控件
                control = map.getControl(TMap.constants.DEFAULT_CONTROL_ID.ZOOM);
                control.setNumVisible(true);

                // 默认添加传入经纬度的标点
                if (lat.val() && lng.val()) {
                    marker = new TMap.MultiMarker({
                        map: map,
                        styles: {
                            "marker": new TMap.MarkerStyle({
                                width: 25,
                                height: 35,
                                anchor: { x: 16, y: 32 }
                            })
                        },
                        geometries: [{
                            id: "marker",
                            styleId: "marker",
                            position: new TMap.LatLng(lat.val(), lng.val())
                        }]
                    });
                }

                // 获取搜索输入框元素
                var inputElement = document.getElementById("search-{$id['lat']}{$id['lng']}");
                
                // 创建建议服务对象
                var suggestion = new TMap.service.Suggestion({
                    pageSize: 10,
                    region: '',
                    regionFix: false
                });

                // 动态创建建议列表容器
                var suggestionListContainer = document.createElement('ul');
                suggestionListContainer.id = 'suggestionList';
                suggestionListContainer.style.position = 'absolute';
                suggestionListContainer.style.zIndex = '2000';
                suggestionListContainer.style.backgroundColor = '#fff';
                suggestionListContainer.style.listStyleType = 'none';
                suggestionListContainer.style.padding = '0';
                suggestionListContainer.style.margin = '0';
                suggestionListContainer.style.border = '1px solid #ccc';
                suggestionListContainer.style.width = inputElement.offsetWidth + 'px'; // 设置宽度与输入框一致
                suggestionListContainer.style.left = inputElement.offsetLeft + 'px'; // 设置与输入框对齐
                suggestionListContainer.style.top = inputElement.offsetTop + inputElement.offsetHeight + 'px'; // 设置在输入框下面
                suggestionListContainer.style.display = 'none'; // 默认隐藏
                inputElement.parentNode.appendChild(suggestionListContainer);

                var suggestionList = [];

                // 更新建议列表 UI
                function updateSuggestionsUI(list) {
                    suggestionListContainer.innerHTML = '';
                    if (list.length > 0) {
                        suggestionListContainer.style.display = 'block'; // 显示建议列表
                        list.forEach(function(item, index) {
                            var listItem = document.createElement('li');
                            listItem.innerHTML = '<a>' + item.title + '<span class="item_info" style="color: #666; margin-left: 5px;">' + item.province +'-'+ item.city +'-'+ item.district + '</span></a>';
                            listItem.style.padding = '5px';
                            listItem.style.cursor = 'pointer';
                            listItem.addEventListener('click', function() {
                                setSuggestion(index);
                            });
                            suggestionListContainer.appendChild(listItem);
                        });
                    } else {
                        suggestionListContainer.style.display = 'none'; // 隐藏建议列表
                    }
                }

                // 设置选中的建议
                function setSuggestion(index) {
                    var selectedSuggestion = suggestionList[index];
                    inputElement.value = selectedSuggestion.title;
                    lat.val(selectedSuggestion.location.lat.toFixed(6));
                    lng.val(selectedSuggestion.location.lng.toFixed(6));
                    map.setCenter(new TMap.LatLng(selectedSuggestion.location.lat, selectedSuggestion.location.lng));
                    if (marker) {
                        marker.setGeometries([]);
                    }
                    
                    marker = new TMap.MultiMarker({
                        map: map,
                        styles: {
                            "marker": new TMap.MarkerStyle({
                                width: 25,
                                height: 35,
                                anchor: { x: 16, y: 32 }
                            })
                        },
                        geometries: [{
                            id: "marker",
                            styleId: "marker",
                            position: new TMap.LatLng(selectedSuggestion.location.lat, selectedSuggestion.location.lng)
                        }]
                    });
                    suggestionListContainer.innerHTML = '';
                    suggestionListContainer.style.display = 'none';
                }

                // 监听输入框的输入事件
                inputElement.addEventListener('input', function() {
                    var keyword = inputElement.value.trim();
                    if (keyword.length > 0) {
                        suggestion.getSuggestions({ keyword: keyword, servicesk: '{$this->sk}' })
                            .then(function(result) {
                                suggestionList = result.data;
                                updateSuggestionsUI(suggestionList);
                            })
                            .catch(function(error) {
                                console.error('Error fetching suggestions:', error);
                            });
                    } else {
                        suggestionListContainer.innerHTML = '';
                        suggestionListContainer.style.display = 'none'; // 隐藏建议列表
                    }
                });

                // 绑定地图点击事件
                map.on("click",function(evt){
                    var lat_position = evt.latLng.getLat().toFixed(6);
                    var lng_position = evt.latLng.getLng().toFixed(6);
                    lat.val(lat_position);
                    lng.val(lng_position);
                    
                    // 手动选点不需要更新中心点坐标
                    // map.setCenter(new TMap.LatLng(lat_position, lng_position));
                    if (marker) {
                        marker.setGeometries([]);
                    }
                    marker = new TMap.MultiMarker({
                        map: map,
                        styles: {
                            "marker": new TMap.MarkerStyle({
                                width: 25,
                                height: 35,
                                anchor: { x: 16, y: 32 }
                            })
                        },
                        geometries: [{
                            id: "marker",
                            styleId: "marker",
                            position: new TMap.LatLng(lat_position, lng_position)
                        }]
                    });
                });
                
            }

            init('{$id['lat']}{$id['lng']}');
        })();
EOT;
    }
}