function interactive(e,t){let n=document.getElementById(e);n.classList.add("interactive"),n.style.zIndex="inherit",
void 0===t?(resizable(n),
close(n),minMax(n)):(!1!==t.resize&&resizable(n),!1!==t.drag&&draggable(n),!1!==t.close&&close(n),!1!==t.minMax&&minMax(n,t.minZone,t.minMaxIcons,t.minDoubleClick)),n.onmousedown=changeStackOrder}
function resizable(e){e.classList.add("resizable");let t=createElementWithIdAndClassName("div","parent_"+e.id,"parentResize");
t.style.zIndex=1,e.parentElement.appendChild(t),t.appendChild(e),addResizePoints(e,t)}function addResizePoints(e,t)
{initialResizeCssProperties(e,t);let n=["left","upperLeft","top","upperRight","right","lowerRight","bottom","lowerLeft"];for(let i=0,o=n.length;i<o;i++){
	let o=createElementWithClassName("div",n[i]);t.appendChild(o),addResizePointFunctionality(e,t,o)}}function initialResizeCssProperties(e,t){
		let n=getComputedStyle(e),i=n.getPropertyValue("width"),o=n.getPropertyValue("height");"0px"==i&&(i="200px"),"0px"==o&&(o="150px"),
t.style.top=n.getPropertyValue("top"),t.style.left=n.getPropertyValue("left"),
t.style.gridTemplateRows="3px "+o+" 3px",t.style.gridTemplateColumns="3px "+i+" 3px",
t.style.backgroundColor=n.getPropertyValue("background-color"),e.style.top="none",e.style.left="none",
e.style.width=i,e.style.height=o}function addResizePointFunctionality(e,t,n)
{n.onmousedown=function(){1==event.which&&trackMouseDragPlusAction({action:"resize",param:[e,t,n.className]})}}
function changeElementSizeAndPosition(e,t,n,i){let o=getResizePointZone(n);changeHorizontalMeasures(e,t,i.x,o[0]),changeVerticalMeasures(e,t,i.y,o[1])}
function getResizePointZone(e){return[getHorizontalResizePointZone(e),getVerticalResizePointZone(e)]}function getHorizontalResizePointZone(e){
return"left"==e||"upperLeft"==e||"lowerLeft"==e?0:"right"==e||"upperRight"==e||"lowerRight"==e?1:void 0}function getVerticalResizePointZone(e){
return"top"==e||"upperLeft"==e||"upperRight"==e?2:"bottom"==e||"lowerLeft"==e||"lowerRight"==e?3:void 0}
function changeHorizontalMeasures(e,t,n,i){if(void 0===i)return;1==i&&(n=-n);
let o=parseInt(e.style.width.slice(0,-2))+n,l=t.offsetLeft;if(!(1==i&&l+o+6>document.body.clientWidth)&&o>=5)
{if(0==i){let e=l-n;if(e<0)return;t.style.left=e+"px"}e.style.width=o+"px",t.style.gridTemplateColumns="3px "+o+"px 3px"}}
function changeVerticalMeasures(e,t,n,i){if(void 0===i)return;3==i&&(n=-n);
let o=parseInt(e.style.height.slice(0,-2))+n,l=t.offsetTop;if(!(3==i&&l+o+6>document.body.clientHeight)&&o>=5)
{if(2==i){let e=l-n;if(e<0)return;t.style.top=e+"px"}e.style.height=o+"px",t.style.gridTemplateRows="3px "+o+"px 3px"}}
function draggable(e){e.classList.add("draggable");let t=createElementWithIdAndClassName("div",e.id+"Header","dragPoint");
initialDragPointStyling(t);let n=e.firstChild;if(null!==n?e.insertBefore(t,n):e.appendChild(t),e.classList.contains("resizable")){
let n=e.parentElement;n.classList.contains("parentResize")&&(resizePointsStyling(e,t),e=n)}t.onmousedown=function(){
1==event.which&&trackMouseDragPlusAction({action:"drag",param:[e]})}}function initialDragPointStyling(e){
e.style.width="100%",e.style.height="20px",e.style.backgroundColor="rgb(48, 55, 97)"}function resizePointsStyling(e,t){
for(let n=0;n<5;n++){
let n=e.nextSibling;"left"==n.className||"right"==n.className?n.style.borderTop=t.style.height+" solid "+t.style.backgroundColor:n.style.backgroundColor=t.style.backgroundColor,e=n}}
function getDragNewPosition(e,t){let n=getElementOffsetAndMeasures(e),i={x:n.left-t.x,y:n.top-t.y};
return preventDragOutsideScreen(n,i,{left:i.x,top:i.y,right:i.x+n.width,bottom:i.y+n.height})}
function getElementOffsetAndMeasures(e){return{left:e.offsetLeft,top:e.offsetTop,height:e.offsetHeight,width:e.offsetWidth}}
function preventDragOutsideScreen(e,t,n){let i=getDocumentBodyLimits();
return n.left<i.left&&(t.x=e.left),n.top<i.top&&(t.y=e.top),n.right>i.right&&(t.x=e.left),n.bottom>i.bottom&&(t.y=e.top),t}
function dragAction(e,t){if("resize"==e.action&&changeElementSizeAndPosition(e.param[0],e.param[1],e.param[2],t),"drag"==e.action){
let n=getDragNewPosition(e.param[0],t);e.param[0].style.left=n.x+"px",e.param[0].style.top=n.y+"px"}}
function close(e){addFunctionButton(e,[{name:"close",path:"M45.1,41.6l-3.3,3.3L5.1,8.2l3.3-3.3L45.1,41.6z M8.4,45.1l-3.3-3.3L41.8,5.1l3.3,3.3L8.4,45.1z"}]),addCloseFunctionality(e)}
function addCloseFunctionality(e){closeBtn=getButton(e,"closeBtn"),closeBtn.onclick=function(){let t=e.parentNode;e.classList.contains("resizable")?t.parentNode.removeChild(t):t.removeChild(e)}}function minMax(e,t,n,i){if(!1!==n){addFunctionButton(e,[{},{name:"min",path:"M5,27.3v-4.6H45v4.6H5z"}])}addMinimizeFunction(e,t,n,i),addFullScreenMaximizeFunction(e)}function addFunctionButton(e,t){let n="path";e.classList.contains("draggable")&&(e=e.firstElementChild,n="dragPath");
let i=createButtonsContainer(e);for(let e=0,o=t.length;e<o;e++){
let o=createElementWithClassName("button",t[e].name+"Btn mmcBtn"),l=createSvgShape({svg:[{attr:"class",value:"svgIcon"},
{attr:"viewBox",value:"0 0 50 50"}],shape:[{shape:"path",attrList:[{attr:"d",value:t[e].path},{attr:"class",value:n}]}]});o.appendChild(l),i.appendChild(o)}
let o=e.firstChild;null!=o?e.insertBefore(i,o):e.appendChild(i)}
function createButtonsContainer(e){let t=e.firstElementChild;
return null!==t&&"btnContainer"===t.className||(t=createElementWithClassName("div","btnContainer")),t}
function addMinimizeFunction(e,t,n,i){if(addMinimizeArea(t),!1!==i&&(e.classList.contains("draggable")&&(e=e.firstElementChild),e.ondblclick=minimize),!1!==n){getButton(e,"minBtn").onclick=minimize}}
function addMinimizeArea(e){if(null==document.getElementById("minimizeZone")){let t=document.createElement("div");t.id="minimizeZone",null==e?document.body.append(t):e.append(t)}}
function minimize(){let e;"dblclick"==event.type&&(e=storeMinimizedElement(this)),"click"==event.type&&(e=storeMinimizedElement(this.parentNode.parentNode)),deleteDuplicatedItemsMinStorage()||minimizeUI(e)}let count,elementWidth,dropdown,numItems,minStorage=[];
function storeMinimizedElement(e){let t={id:e.id,title:e.getAttribute("name")};
return e.classList.contains("resizable")&&(e=e.parentNode),e.classList.contains("dragPoint")&&(e=e.parentNode,t.id=e.id,t.title=e.getAttribute("name"),e.classList.contains("resizable")&&(e=e.parentNode)),minStorage.push(t),e}
function deleteDuplicatedItemsMinStorage(){return(count=minStorage.length-1)>0&&minStorage[count-1].id==minStorage[count].id&&(minStorage.pop(),!0)}
function getItemCountToFitElementByWidth(e,t){if(null!=e){let n=t.clientWidth,i=window.getComputedStyle(e),o=parseInt(i.getPropertyValue("width").slice(0,-2)),l=parseInt(i.getPropertyValue("margin-left").slice(0,-2)),a=parseInt(i.getPropertyValue("margin-right").slice(0,-2));return elementWidth=o+l+a,Math.floor(n/elementWidth)}}
function createMinimizedElementRep(e,t){let n=createElementWithIdAndClassName("span",e,"minimizedItem"),i=createElementWithClassName("p","minimizedTitle");return i.textContent=t,i.setAttribute("onselectstart","return false;"),n.appendChild(i),n}function minimizeUI(e){let t=document.getElementById("minimizeZone");if(void 0===dropdown)if(numItems=getItemCountToFitElementByWidth(t.firstElementChild,t),minStorage.length>numItems)horizontalRepToDropdownList(numItems,t),dropdown=!0;else{let e=createMinimizedElementRep(""+count,minStorage[count].title);t.appendChild(e),e.onclick=maximize}else if(!0===dropdown){let e=document.getElementById("dropdownList");addDropdownItem(""+count,minStorage[count].title,e)}e.style.display="none"}
function maximize(){this.parentNode.removeChild(this);let e=parseInt(this.id),t=document.getElementById(minStorage[e].id);if(t.classList.contains("resizable")?t.parentElement.style.display="grid":t.style.display="block",minStorage[e]="",!0===dropdown){let e=document.getElementById("dropdownList");e.childElementCount<=numItems&&fromDropdownToHorizontalMinimized(e)}if(void 0===dropdown){let e=document.getElementById("minimizeZone");if(0==e.childElementCount)minStorage.length=0;else{let t=e.childNodes;for(let e=0,n=t.length;e<n;e++)t[e].id=e;for(let e=0,t=minStorage.length;e<t;e++)""==minStorage[t-1-e]&&minStorage.splice(t-1-e,1)}}}
function horizontalRepToDropdownList(e,t){deleteMinimizedItems(e,t);let n=addDropdown(t);addDropdownItems(minStorage,n)}
function fromDropdownToHorizontalMinimized(e){let t=e.previousSibling,n=e.parentElement;n.removeChild(t),n.removeChild(e),dropdown=void 0;let i=0;for(let e=0,t=minStorage.length;e<t;e++)""!=minStorage[e]&&(minStorage[i]={id:minStorage[e].id,title:minStorage[e].title},i++);minStorage.length=i,minimizeArea=document.getElementById("minimizeZone");for(let e=0,t=minStorage.length;e<t;e++){let t=createMinimizedElementRep(""+e,minStorage[e].title);minimizeArea.appendChild(t),
t.onclick=maximize}}
function deleteMinimizedItems(e,t){for(let n=0;n<e;n++){
let e=document.getElementById(""+n);t.removeChild(e)}}
function addDropdown(e){let t=createElementWithIdAndClassName("button","dropdownBtn","dropdownBtn");
t.innerHTML="&#11205;",t.setAttribute("onselectstart","return false;");
let n=document.createElement("div");
return n.id="dropdownList",e.appendChild(t),e.appendChild(n),
t.onclick=function(){"block"==n.style.display?(n.style.display="none",t.innerHTML="&#11205;"):(n.style.display="block",t.innerHTML="&#11206;")},n}
function addDropdownItems(e,t){for(let n=0,i=e.length;n<i;n++)addDropdownItem(""+n,e[n].title,t)}
function addDropdownItem(e,t,n){let i=createElementWithIdAndClassName("div",e,"dropdownItem");i.textContent=t,i.setAttribute("onselectstart","return false;"),n.appendChild(i),i.onclick=maximize}let maxStorage={};
function addFullScreenMaximizeFunction(e){
null!=t&&null!=t&&(t.onclick=function(){let t,n=""+e.id;if(e.classList.contains("resizable")&&(t=!0),void 0===maxStorage[n]){
let n=window.innerWidth||document.documentElement.clientWidth||document.body.clientWidth,i=window.innerHeight||document.documentElement.clientHeight||document.body.clientHeight,o=e.id;if(t){
let t=e.parentNode;maxStorage[o]={actualSize:getElementSizeAndPosition(t)},e.style.width="100%",e.style.height="100%",t.style.top="0px",t.style.left="0px",t.style.margin="0px",t.style.gridTemplateRows="3px "+(i-6)+"px 3px",t.style.gridTemplateColumns="3px "+(n-6)+"px 3px"}
else 
maxStorage[o]={actualSize:getElementSizeAndPosition(e)},e.style.top="0px",e.style.left="0px",e.style.margin="0px",e.style.width=n+"px",e.style.height=i+"px"}
else{if(t){let t=e.parentElement;t.style.top=maxStorage[n].actualSize.top,t.style.left=maxStorage[n].actualSize.left,t.style.margin=maxStorage[n].actualSize.margin,t.style.gridTemplateRows=maxStorage[n].actualSize.gridRow,t.style.gridTemplateColumns=maxStorage[n].actualSize.gridCol}
else e.style.top=maxStorage[n].actualSize.top,e.style.left=maxStorage[n].actualSize.left,e.style.width=maxStorage[n].actualSize.width,e.style.margin=maxStorage[n].actualSize.margin,e.style.height=maxStorage[n].actualSize.height;delete maxStorage[n]}})}
function getButton(e,t){let n;for(let i=0,o=(n=e.classList.contains("draggable")?e.firstElementChild.firstElementChild.childNodes:e.firstElementChild.childNodes).length;i<o;i++)if(n[i].classList.contains(t))return n[i]}
function getElementSizeAndPosition(e){let t=window.getComputedStyle(e);
return{width:t.getPropertyValue("width"),height:t.getPropertyValue("height"),top:t.getPropertyValue("top"),left:t.getPropertyValue("left"),gridCol:t.getPropertyValue("grid-template-columns"),gridRow:t.getPropertyValue("grid-template-rows")}}
function changeStackOrder(){let e=document.getElementsByClassName("interactive");for(let t=0,n=e.length;t<n;t++)e[t]==this?e[t].classList.contains("resizable")?e[t].parentElement.style.zIndex=2:e[t].style.zIndex=2:e[t].classList.contains("resizable")?e[t].parentElement.style.zIndex=1:e[t].style.zIndex="inherit"}function onWindowResize(){resizeOnWindowChange(),updateMinimizedItemsOnWindowChange()}function onWindowLoad(){resizeOnWindowChange()}function resizeOnWindowChange(){let e=document.getElementsByClassName("parentResize");for(let t=0,n=e.length;t<n;t++){let n=document.body.clientWidth,i=document.body.clientHeight,o=e[t].firstElementChild,l=e[t],a=l.offsetLeft;if(a+(parseInt(o.style.width.slice(0,-2))+6)>n){let e=n-a-6;if(e>=5)l.style.gridTemplateColumns="3px "+e+"px 3px",o.style.width=e+"px";else{let e=n-5-6;l.style.left=e+"px"}}let r=l.offsetTop;if(r+(parseInt(o.style.height.slice(0,-2))+6)>i){let e=i-r-6;if(e>=5)l.style.gridTemplateRows="3px "+e+"px 3px",o.style.height=e+"px";else{let e=i-5-6;e>=0&&(l.style.top=e+"px")}}}}function updateMinimizedItemsOnWindowChange(){let e=document.getElementById("minimizeZone");void 0===dropdown?(numItems=getItemCountToFitElementByWidth(e.firstElementChild,e),minStorage.length>numItems&&(horizontalRepToDropdownList(numItems+1,e),dropdown=!0)):(numItems=Math.floor(e.clientWidth/elementWidth),minStorage.length<=numItems&&(fromDropdownToHorizontalMinimized(e.firstElementChild.nextSibling),dropdown=void 0))}function createElementWithIdAndClassName(e,t,n){let i=document.createElement(e);return i.id=t,i.className=n,i}function createElementWithClassName(e,t){let n=document.createElement(e);return n.className=t,n}function getDocumentBodyLimits(){return{left:0,right:document.body.clientWidth,top:0,bottom:document.body.clientHeight}}function trackMouseDragPlusAction(e){let t=event.clientX,n=event.clientY;document.onmouseup=dragMouseStop,document.onmousemove=function(){let i=event.clientX,o=event.clientY;dragAction(e,{x:t-i,y:n-o}),t=i,n=o}}function dragMouseStop(){document.onmouseup=null,document.onmousemove=null}function createSvgShape(e){let t=document.createElementNS("http://www.w3.org/2000/svg","svg");for(let n=0,i=e.svg.length;n<i;n++)t.setAttribute(e.svg[n].attr,e.svg[n].value);for(let n=0,i=e.shape.length;n<i;n++){let i=document.createElementNS("http://www.w3.org/2000/svg",e.shape[n].shape);for(let t=0,o=e.shape[n].attrList.length;t<o;t++)i.setAttribute(e.shape[n].attrList[t].attr,e.shape[n].attrList[t].value);t.appendChild(i)}return t}window.onresize=onWindowResize,window.onload=onWindowLoad;
